<?php
namespace app\components;

use app\models\AoRequestsApplications;
use app\models\MroRequestApply;
use app\models\Requests;
use app\models\RepairReport;
use yii\db\Expression;
use yii\db\Query;

final class MroRequestCounters
{
    public static function forMro(int $mroId): array
    {
        if ($mroId <= 0) {
            return ['open' => 0, 'closed' => 0];
        }
        $selectedApplication = (new Query())
            ->select('selected_application.id')
            ->from(['selected_po' => AoRequestsApplications::tableName()])
            ->innerJoin(['selected_application' => MroRequestApply::tableName()],
                'selected_application.id = selected_po.application_id AND selected_application.request_id = selected_po.request_id')
            ->where('selected_po.request_id = r.request_id')
            ->orderBy(['selected_po.id' => SORT_DESC])
            ->limit(1);
        $selectedRequests = Requests::find()->alias('r')
            ->innerJoin(['a' => MroRequestApply::tableName()], 'a.request_id = r.request_id')
            ->where(['a.mro_id' => $mroId])
            ->andWhere(['=', 'a.id', $selectedApplication]);
        $hasPo = (new Query())->select(new Expression('1'))
            ->from(['po' => AoRequestsApplications::tableName()])
            ->where('po.application_id = a.id')
            ->andWhere(['not', ['po.po' => null]])
            ->andWhere(['<>', 'po.po', '']);
        // Match the Open Requests list's eligibility, without search or pagination.
        $open = (clone $selectedRequests)
            ->andWhere(['or', ['not in', 'r.status', ['closed', 'created']], ['r.status' => null]])
            ->andWhere(['or', ['<>', 'r.status', 'answered'], ['r.status' => null], ['exists', $hasPo]])
            ->count('DISTINCT r.request_id');
        // The list dates closure from the latest approved report for the request.
        // Its latest date is within the period iff an approved report is within it.
        $recentClosure = (new Query())->select(new Expression('1'))
            ->from(['closing_report' => RepairReport::tableName()])
            ->innerJoin(['closing_application' => MroRequestApply::tableName()],
                'closing_application.id = closing_report.mro_request_apply_id')
            ->where('closing_application.request_id = r.request_id')
            ->andWhere(['closing_report.quote_approved' => 1])
            ->andWhere(['>=', 'closing_report.updated_at', date('Y-m-d H:i:s', strtotime('-3 months'))]);
        $closed = (clone $selectedRequests)->andWhere(['r.status' => 'closed'])
            ->andWhere(['exists', $recentClosure])
            ->count('DISTINCT r.request_id');
        return ['open' => (int) $open, 'closed' => (int) $closed];
    }
}
