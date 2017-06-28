<?php
return [
    'adminEmail' => 'admin@example.com',
    'upload.maxsize' => '20', //MB
	// 图片服务器的域名设置，拼接保存在数据库中的相对地址，可通过web进行展示
	'domain' => 'http://admin.hdyzs.com/',
	'webuploader' => [
		// 后端处理图片的地址，value 是相对的地址
		'uploadUrl' => 'expert/upload',
		// 多文件分隔符
		'delimiter' => ',',
		// 基本配置
		'baseConfig' => [
			'defaultImage' => 'http://img1.imgtn.bdimg.com/it/u=2056478505,162569476&fm=26&gp=0.jpg',
			'disableGlobalDnd' => true,
			'accept' => [
				'title' => 'Images',
				'extensions' => 'gif,jpg,jpeg,bmp,png',
				'mimeTypes' => 'image/*',
			],
			'pick' => [
				'multiple' => false,
			],
		],
	],
	'imageUploadRelativePath' => './uploads/', // 图片默认上传的目录
	'imageUploadSuccessPath' => 'uploads/', // 图片上传成功后，路径前缀
];
