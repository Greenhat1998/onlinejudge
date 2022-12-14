<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Html::encode($model->title);
$files = $model->getDataFiles();
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Problems'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['model'] = $model;
?>
<div class="solutions-view">
    <h1>
        <?= Html::encode($model->title) ?>
    </h1>

    <?= \app\widgets\webuploader\MultiImage::widget() ?>

    <p></p>
    <div class="row table-responsive">
        <div class="col-md-12">
            <?php if (extension_loaded('zip')): ?>
                <p>
                    <?= Html::a('Download all data', ['download-data', 'id' => $model->id], ['class' => 'btn btn-success']); ?>
                </p>
            <?php else: ?>
                <p>
                    The server does not enable the php-zip extension. If you need to download the test data, please install the php-zip extension.
                </p>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <table class="table table-bordered table-rank">
                <caption>
                    Standard input file
                    <a href="<?= Url::toRoute(['/admin/problem/deletefile', 'id' => $model->id,'name' => 'in']) ?>" onclick="return confirm('Are you sure to delete all input files? ');">
                        Delete all input files
                    </a>
                </caption>
                <tr>
                    <th>File name </th>
                    <th>Size </th>
                    <th>Time </th>
                    <th>Action </th>
                </tr>
                <?php foreach ($files as $file): ?>
                    <?php
                    if (!strpos($file['name'], '.in'))
                        continue;
                    ?>
                    <tr>
                        <th><?= $file['name'] ?></th>
                        <th><?= $file['size'] ?></th>
                        <th><?= date('Y-m-d H:i', $file['time']) ?></th>
                        <th>
                            <a href="<?= Url::toRoute(['/admin/problem/viewfile', 'id' => $model->id,'name' => $file['name']]) ?>"
                               target="_blank"
                               title="<?= Yii::t('app', 'View') ?>">
                                <span class="glyphicon glyphicon-eye-open"></span>
                            </a>
                            &nbsp;
                            <a href="<?= Url::toRoute(['/admin/problem/deletefile', 'id' => $model->id,'name' => $file['name']]) ?>"
                               title="<?= Yii::t('app', 'Delete') ?>">
                                <span class="glyphicon glyphicon-remove"></span>
                            </a>
                        </th>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-bordered table-rank">
                <caption>
                    Standard output file
                    <a href="<?= Url::toRoute(['/admin/problem/deletefile', 'id' => $model->id, 'name' => 'out']) ?>" onclick="return confirm('Are you sure to delete all output files? ');">
                        Delete all output file
                    </a>
                </caption>
                <tr>
                    <th>File name </th>
                    <th>Size </th>
                    <th>Time </th>
                    <th>Action </th>
                </tr>
                <?php foreach ($files as $file): ?>
                    <?php
                    if (!strpos($file['name'], '.out') && !strpos($file['name'], '.ans'))
                        continue;
                    ?>
                    <tr>
                        <th><?= $file['name'] ?></th>
                        <th><?= $file['size'] ?></th>
                        <th><?= date('Y-m-d H:i', $file['time']) ?></th>
                        <th>
                            <a href="<?= Url::toRoute(['/admin/problem/viewfile', 'id' => $model->id,'name' => $file['name']]) ?>"
                               target="_blank"
                               title="<?= Yii::t('app', 'View') ?>">
                                <span class="glyphicon glyphicon-eye-open"></span>
                            </a>
                            &nbsp;
                            <a href="<?= Url::toRoute(['/admin/problem/deletefile', 'id' => $model->id,'name' => $file['name']]) ?>"
                               title="<?= Yii::t('app', 'Delete') ?>">
                                <span class="glyphicon glyphicon-remove"></span>
                            </a>
                        </th>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
