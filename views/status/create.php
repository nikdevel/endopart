<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Status */

$this->title = 'Добавить статус';
$this->params['breadcrumbs'][] = ['label' => 'Администрирование', 'url' => ['/user/index']];
$this->params['breadcrumbs'][] = ['label' => 'Статусы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="status-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
