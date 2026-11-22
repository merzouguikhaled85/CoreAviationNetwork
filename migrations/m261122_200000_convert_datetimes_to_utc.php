<?php

use yii\db\Migration;

/**
 * PASSAGE DES DATES EXISTANTES EN UTC.
 *
 * Avant cette migration, les dates étaient écrites dans le fuseau du serveur :
 *  - par PHP (date('Y-m-d H:i:s')) dans le fuseau de php.ini ;
 *  - par MySQL (CURRENT_TIMESTAMP) dans le fuseau du serveur MySQL.
 * La plateforme travaille désormais uniquement en UTC (config/web.php,
 * config/console.php et config/db.php). Cette migration détecte les deux
 * anciens décalages sur le serveur où elle s'exécute (local ou production) et
 * décale les valeurs existantes en conséquence.
 *
 * Règles par colonne DATETIME / TIMESTAMP :
 *  - colonnes déjà en UTC : ignorées (nom en *_utc, audit_log.created_at) ;
 *  - DATETIME avec DEFAULT CURRENT_TIMESTAMP : écrite par MySQL -> - décalage MySQL ;
 *  - autre DATETIME : écrite par PHP -> - décalage PHP ;
 *  - TIMESTAMP avec DEFAULT CURRENT_TIMESTAMP : stockée en UTC par MySQL -> rien ;
 *  - autre TIMESTAMP : texte PHP interprété par MySQL -> + (décalage MySQL - décalage PHP).
 *
 * Chaque décalage appliqué est enregistré dans {{%timezone_conversion_log}} :
 * down() les annule exactement. Si les deux décalages valent 0 (serveur déjà en
 * UTC), aucune donnée n'est modifiée.
 *
 * Limite connue : le décalage actuel est appliqué à tout l'historique ; un
 * serveur réglé sur un fuseau avec heure d'été garderait 1 h d'écart sur les
 * dates de l'autre saison (Africa/Tunis n'a pas d'heure d'été).
 */
class m261122_200000_convert_datetimes_to_utc extends Migration
{
    private const LOG_TABLE = '{{%timezone_conversion_log}}';

    /** Colonnes connues comme déjà écrites en UTC par le code. */
    private const ALREADY_UTC = [
        'audit_log.created_at',
    ];

    public function safeUp()
    {
        $db = $this->db;

        // 1) Anciens décalages, mesurés avant que tout soit en UTC.
        $mysqlOffset = $this->mysqlServerOffset();
        $phpZone = ini_get('date.timezone') ?: 'UTC';
        try {
            $phpOffset = (new DateTimeZone($phpZone))->getOffset(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        } catch (Exception $e) {
            $phpZone = 'UTC';
            $phpOffset = 0;
        }

        echo "    > MySQL server offset: {$mysqlOffset}s, PHP (php.ini {$phpZone}) offset: {$phpOffset}s\n";

        $this->createTable(self::LOG_TABLE, [
            'id' => $this->primaryKey(),
            'table_name' => $this->string(128)->notNull(),
            'column_name' => $this->string(128)->notNull(),
            'shift_seconds' => $this->integer()->notNull(),
            'mysql_offset' => $this->integer()->notNull(),
            'php_offset' => $this->integer()->notNull(),
            'php_zone' => $this->string(64)->notNull(),
            'converted_at' => $this->dateTime()->notNull(),
        ]);

        // 2) Colonnes de date de la base courante.
        $columns = $db->createCommand(
            "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND DATA_TYPE IN ('datetime', 'timestamp')
              ORDER BY TABLE_NAME, ORDINAL_POSITION"
        )->queryAll();

        $logTable = $db->schema->getRawTableName(self::LOG_TABLE);
        $migrationTable = $db->schema->getRawTableName('{{%migration}}');
        $plan = [];

        foreach ($columns as $column) {
            $table = $column['TABLE_NAME'];
            $name = $column['COLUMN_NAME'];

            if ($table === $logTable || $table === $migrationTable
                || in_array($table . '.' . $name, self::ALREADY_UTC, true)
                || preg_match('/_utc$/i', $name)) {
                continue;
            }

            $mysqlDefault = stripos((string) $column['COLUMN_DEFAULT'], 'current_timestamp') !== false;

            if ($column['DATA_TYPE'] === 'datetime') {
                $shift = $mysqlDefault ? -$mysqlOffset : -$phpOffset;
            } else {
                $shift = $mysqlDefault ? 0 : $mysqlOffset - $phpOffset;
            }

            if ($shift !== 0) {
                $plan[$table][$name] = $shift;
            }
        }

        if ($plan === []) {
            echo "    > No column to convert: data is already in UTC.\n";
            return true;
        }

        // 3) Conversion table par table (mode SQL souple pour les dates « zéro »).
        $sqlMode = $db->createCommand('SELECT @@SESSION.sql_mode')->queryScalar();
        $db->createCommand("SET SESSION sql_mode = ''")->execute();

        try {
            foreach ($plan as $table => $shifts) {
                $set = [];
                foreach ($shifts as $name => $shift) {
                    $quoted = $db->quoteColumnName($name);
                    $set[] = "{$quoted} = IF({$quoted} IS NULL OR {$quoted} < '1971-01-02', {$quoted}, {$quoted} + INTERVAL {$shift} SECOND)";
                }

                // Colonnes ON UPDATE CURRENT_TIMESTAMP : réécrites à l'identique pour ne pas passer à « maintenant ».
                foreach ($this->onUpdateColumns($table) as $name) {
                    if (!isset($shifts[$name])) {
                        $quoted = $db->quoteColumnName($name);
                        $set[] = "{$quoted} = {$quoted}";
                    }
                }

                $count = $db->createCommand('UPDATE ' . $db->quoteTableName($table) . ' SET ' . implode(', ', $set))->execute();
                echo "    > {$table}: " . implode(', ', array_map(static fn($n, $s) => "{$n} " . ($s > 0 ? '+' : '') . "{$s}s", array_keys($shifts), $shifts)) . " ({$count} rows)\n";

                foreach ($shifts as $name => $shift) {
                    $this->insert(self::LOG_TABLE, [
                        'table_name' => $table,
                        'column_name' => $name,
                        'shift_seconds' => $shift,
                        'mysql_offset' => $mysqlOffset,
                        'php_offset' => $phpOffset,
                        'php_zone' => $phpZone,
                        'converted_at' => gmdate('Y-m-d H:i:s'),
                    ]);
                }
            }
        } finally {
            $db->createCommand('SET SESSION sql_mode = :mode', [':mode' => $sqlMode])->execute();
        }

        return true;
    }

    public function safeDown()
    {
        $db = $this->db;
        $rows = (new \yii\db\Query())->from(self::LOG_TABLE)->all($db);
        $plan = [];
        foreach ($rows as $row) {
            $plan[$row['table_name']][$row['column_name']] = (int) $row['shift_seconds'];
        }

        $sqlMode = $db->createCommand('SELECT @@SESSION.sql_mode')->queryScalar();
        $db->createCommand("SET SESSION sql_mode = ''")->execute();

        try {
            foreach ($plan as $table => $shifts) {
                $set = [];
                foreach ($shifts as $name => $shift) {
                    $quoted = $db->quoteColumnName($name);
                    $reverse = -$shift;
                    $set[] = "{$quoted} = IF({$quoted} IS NULL OR {$quoted} < '1971-01-02', {$quoted}, {$quoted} + INTERVAL {$reverse} SECOND)";
                }
                foreach ($this->onUpdateColumns($table) as $name) {
                    if (!isset($shifts[$name])) {
                        $quoted = $db->quoteColumnName($name);
                        $set[] = "{$quoted} = {$quoted}";
                    }
                }
                $db->createCommand('UPDATE ' . $db->quoteTableName($table) . ' SET ' . implode(', ', $set))->execute();
            }
        } finally {
            $db->createCommand('SET SESSION sql_mode = :mode', [':mode' => $sqlMode])->execute();
        }

        $this->dropTable(self::LOG_TABLE);

        return true;
    }

    /** Décalage du fuseau du serveur MySQL (secondes), mesuré hors du forçage UTC de la session. */
    private function mysqlServerOffset(): int
    {
        $db = $this->db;
        $db->createCommand('SET time_zone = @@global.time_zone')->execute();
        try {
            return (int) $db->createCommand('SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW())')->queryScalar();
        } finally {
            $db->createCommand("SET time_zone = '+00:00'")->execute();
        }
    }

    /** @return string[] colonnes ON UPDATE CURRENT_TIMESTAMP de la table */
    private function onUpdateColumns(string $table): array
    {
        return $this->db->createCommand(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
                AND EXTRA LIKE '%on update%'",
            [':t' => $table]
        )->queryColumn();
    }
}
