<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\ExpertTime;

/* @var $this yii\web\View */
/* @var $searchModel common\models\ExpertTimeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '预约时段管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="expert-time-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
          //  ['class' => 'yii\grid\SerialColumn'],

            'id',
	        ['attribute'=>'expertName',
		        'label'=>'专家名称',
		        'value'=>'expert.name',
	        ],
	        ['attribute'=>'patientName',
		        'label'=>'患者名称',
		        'value'=>'appointment.patient_name',
	        ],
	        ['attribute'=>'date',
		        'label'=>'时间（Y-m-d）',
		        'value'=>'date',
		        'filter' => \yii\jui\DatePicker::widget([
			        'name'=>'date',
			        'language' => 'zh-CN',
			        'dateFormat' => 'yyyy-MM-dd'
		        ]),
		        'format' => 'html',
	        ],
            'hour',
	        ['attribute'=>'zone',
		        'value'=>'ZoneStr',
		        'filter'=>ExpertTime::allZone(),
		        'headerOptions'=>['width'=>'120px'],
//		        'contentOptions'=>['width'=>'120px'],
	        ],

	        ['attribute'=>'order_status',
		        'label'=>'预约状态',
		        'value'=>'OrderStatus',
		        'filter'=>ExpertTime::allOrderStatus(),
		        'headerOptions'=>['width'=>'120px'],
//		        'contentOptions'=>['width'=>'120px'],
	        ],
	        ['attribute'=>'status',
		        'value'=>'StatusStr',
		        'filter'=>ExpertTime::allStatus(),
				'headerOptions' => ['width' => '120px'],
//		        'contentOptions'=>['width'=>'120px'],
	        ],
           // 'status',
            //'is_order',
            // 'clinic_uuid',
            // 'order_no',
           //  'expertName',
            // 'reason',

	        ['class' => 'yii\grid\ActionColumn',
		        'template' => '{view} {delete} ',
		        'buttons' => [
			        'delete'=>function($url,$model,$key)
			        {
				        if($model->order_no >0){
					        return '';
				        }
				        $options=[
					        'title'=>Yii::t('yii', '删除'),
					        'aria-label'=>Yii::t('yii','删除'),
					        'data-confirm'=>Yii::t('yii','你确定删除这个时段吗？'),
					        'data-method'=>'post',
					        'data-pjax'=>'0',
				        ];
				        return Html::a('<span class="glyphicon glyphicon-trash"></span>',$url,$options);
			        },

		        ]
	        ],
        ],
    ]); ?>
</div>
