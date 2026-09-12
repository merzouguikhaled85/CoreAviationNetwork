<?php
use app\models\Advert;

$randomAdvert = Advert::findRandomActiveAdvert();
$randomAdvert = null;
if ($randomAdvert) {
    // Debugging output

    // Check if the advert type is photo or video
    if ($randomAdvert->advert_type === 'photo') {
        // If it's a photo, display it as an image
        echo '<div style="margin-bottom: 20px; text-align: center;">';
        echo '<img src="' . Yii::getAlias('@web/uploads/' . $randomAdvert->content) . '" style="width: 200px;">';
        echo '</div>';
    } else {
        // Check if the content is a YouTube embed link
        if (preg_match('/https:\/\/www\.youtube\.com\/embed\/([^?]+)/', $randomAdvert->content, $matches)) {
            // If it's a YouTube embed link, embed using iframe with autoplay
            echo '<div style="margin-bottom: 20px; text-align: center;">';
            echo '<iframe width="200" height="150" src="' . $randomAdvert->content . '&autoplay=1" frameborder="0" allowfullscreen></iframe>';
            echo '</div>';
        } else {
            // If it's not a YouTube embed link, assume it's a file path and display it as a video
            echo '<div style="margin-bottom: 20px; text-align: center;">';
            echo '<video width="200" controls>';
            echo '<source src="' . Yii::getAlias('@web/uploads/' . $randomAdvert->content) . '" type="video/mp4">';
            echo 'Your browser does not support the video tag.';
            echo '</video>';
            echo '</div>';
        }
    }
}
?>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
