<?php
namespace api\modules\v1\controllers;

use yii;
use api\modules\ApiBaseController;
use api\models\User;
use yii\helpers\ArrayHelper;
use common\service\Service;
use common\models\VerifycodeCache;

class UserController extends ApiBaseController
{
    public $modelClass = 'api\models\User';//对应的数据模型处理控制器

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'optional' => [
                    'create',
                    'test',
                    'login',
                    'sendcode',
                    'resetpassword',
                ],
            ]
        ]);
    }

    public function actions()
    {
        $actions = parent::actions();
        // 禁用""index,delete" 和 "create" 操作
//        unset($actions['index'], $actions['delete'], $actions['create'], $actions['update']);

        return $actions;

    }


    public function actionTest($score)
    {
        //当前用户
        $user = \yii::$app->user->identity;

        $clinic = $user->clinicUu;
        $old_score = $clinic->score;

        $clinic->updateScore($score);

        $data = ['old_score' => $old_score,'add_score'=>$score];

//        $data = array('123');
        return Service::sendSucc($data);
        return Service::sendError('20302','data error');
    }

    public function actionSendcode($mobile){
        $model = VerifycodeCache::findOne(['mobile'=>$mobile]);
        if(!$model){
            $model = new VerifycodeCache();
            $model->mobile = $mobile;
        }
        $smscode = Service::createSmsCode();
        $model->code = "$smscode";
        $model->expire_time = time()+30*60;

        $sms_content = sprintf(Yii::$app->params['verifycode.msg'],$smscode);
        if($model->save() && Service::sendSms($mobile,$sms_content) == 0){
            //发送短信给用户
            return Service::sendSucc();
        }else{
            return Service::sendError(20102,'验证码生成出错');
        }
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $post = Yii::$app->request->post();

        //参数检查
        if(!$post['mobile'] || !$post['code'] || !$post['password'] || !$post['password_confirm']){
            return Service::sendError(10400,'缺少参数');
        }

        //检查验证码是否正确，上线前需要打开
        $verfiycode = VerifycodeCache::find()
            ->where(['mobile' => $post['mobile']])
            ->andWhere(['code' => $post['code']])
            ->andWhere(['>=','expire_time',time()])
            ->one();
        if(!$verfiycode){
            return Service::sendError(20103,'验证码错误');
        }

        //检查手机号是否存在
        if(User::findOne(['mobile' => $post['mobile']])){
            return Service::sendError(20104,'手机号已存在');
        }

        //检查用户名是否存在（可以为空）
        if(isset($post['username']) && User::findOne(['username' => $post['username']])){
            return Service::sendError(20105,'用户名已存在');
        }

        //检查邮箱是否合法并且是否存在（可以为空）
        if(isset($post['email'])){
            if(!Service::isEmail($post['email'])){
                return Service::sendError(20106,'邮箱格式不正确');
            }
            if(User::findOne(['email' => $post['email']])){
                return Service::sendError(20107,'邮箱已存在');
            }
        }
        //检查密码强度并且检查两次输入的是否一样
        if(strlen($post['password']) < 6){
            return Service::sendError(20108,'密码长度不能小于6');
        }

        if($post['password'] !== $post['password_confirm']){
            return Service::sendError(20109,'两次输入密码不一致');
        }

        $user = new User();
        isset($post['username']) && $user->username = $post['username'];
        isset($post['email']) && $user->email = $post['email'];
        $user->mobile = $post['mobile'];
        $user->setPassword($post['password']);
        $user->generateAuthKey();
        $user->uuid = Service::create_uuid();
        if($user->save()){
            return Service::sendSucc();
        }else{
            return Service::sendError(20110,'注册出错');
        }
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionResetpassword()
    {
        $post = Yii::$app->request->post();

        //参数检查
        if(!isset($post['mobile']) || !isset($post['code']) || !isset($post['password']) || !isset($post['password_confirm'])){
            return Service::sendError(10400,'缺少参数');
        }

        //检查验证码是否正确
        $verfiycode = VerifycodeCache::find()
            ->where(['mobile' => $post['mobile']])
            ->andWhere(['code' => $post['code']])
            ->andWhere(['>=','expire_time',time()])
            ->one();
        if(!$verfiycode){
            return Service::sendError(20103,'验证码错误');
        }

        //检查密码强度并且检查两次输入的是否一样
        if(strlen($post['password']) < 6){
            return Service::sendError(20108,'密码长度不能小于6');
        }

        if($post['password'] !== $post['password_confirm']){
            return Service::sendError(20109,'两次输入密码不一致');
        }

        //检查用户是否存在
        $user = User::findOne(['mobile' => $post['mobile']]);
        if(!$user){
            return Service::sendError(20112,'用户不存在');
        }

        $user->setPassword($post['password']);
        $user->removePasswordResetToken();
        if($user->save()){
            return Service::sendSucc();
        }else{
            return Service::sendError(20113,'修改密码出错');
        }
    }

    public function actionUpdate()
    {
        //当前用户
        $user = \yii::$app->user->identity;
        $post = Yii::$app->request->post();

        //参数检查
        if(!isset($post['username']) && !isset($post['email'])){
            return Service::sendError(10400,'缺少参数');
        }

        //检查用户名是否存在（可以为空）
        if(isset($post['username'])
            && User::find()->where(['<>','uuid',$user->uuid])
                ->andWhere(['username' => $post['username']])
                ->one()){
            return Service::sendError(20105,'用户名已存在');
        }

        //检查邮箱是否合法并且是否存在（可以为空）
        if(isset($post['email'])){
            if(!Service::isEmail($post['email'])){
                return Service::sendError(20106,'邮箱格式不正确');
            }
            if(User::find()->where(['<>','uuid',$user->uuid])
                ->andWhere(['email' => $post['email']])
                ->one()){
                return Service::sendError(20107,'邮箱已存在');
            }
        }

        isset($post['username']) && $user->username = $post['username'];
        isset($post['email']) && $user->email = $post['email'];
        if($user->save()){
            return Service::sendSucc();
        }else{
            return Service::sendError(20114,'编辑用户信息出错');
        }
    }

    //当前用户信息
    public function actionProfile()
    {
        $user = \yii::$app->user->identity;
        $info = $user->attributes;
        unset($info['id'],$info['auth_key'],$info['password_hash'],$info['password_reset_token'],$info['status'],
        $info['api_token'],$info['type']);
        //诊所获取积分信息
        if($user->type == User::USER_CLINIC){
            $clinic = $user->clinicUu;
            if($clinic){
                $clinicAttributes = $clinic->attributes;
                unset($clinicAttributes['id'],$clinicAttributes['user_uuid']);
                $clinicAttributes['Business_license_img'] = rtrim(Yii::$app->params['domain'],'/').'/'.$clinicAttributes['Business_license_img'];
                $clinicAttributes['local_img'] = rtrim(Yii::$app->params['domain'],'/').'/'.$clinicAttributes['local_img'];
                $clinicAttributes['doctor_certificate_img'] = rtrim(Yii::$app->params['domain'],'/').'/'.$clinicAttributes['doctor_certificate_img'];
                $info['clinic'] = $clinicAttributes;
            }
        }else if($user->type == User::USER_EXPERT){
            $expert = $user->expertUu;
            if($expert){
                $expertAttributes = $expert->attributes;
                unset($expertAttributes['id'],$expertAttributes['user_uuid']);
                $expertAttributes['head_img'] = rtrim(Yii::$app->params['domain'],'/').'/'.$expertAttributes['head_img'];
                if(!empty($expertAttributes['free_time'])){
                    $expertAttributes['free_time'] = json_decode($expertAttributes['free_time'],true);
                }
                $info['expert'] = $expertAttributes;
            }
        }
        return Service::sendSucc($info);
    }
//
//    public function actionDelete()
//    {
//        $post = Yii::$app->request->post();
//        $user = User::findOne($post['id']);
//        if (!$user) {
//            $code = 21003;
//            $message = '用户不存在';
//            return [
//                'code' => $code,
//                'data' => [],
//                'message' => $message,
//            ];
//        }
//        $user->status = 0;
//        if ($user->save()) {
//            return [];
//        } else {
//            $code = 21003;
//            $message = array_values($user->getFirstErrors())[0];
//            return [
//                'code' => $code,
//                'data' => [],
//                'message' => $message,
//            ];
//        }
//    }
//
//
//
////    public function actionIndex()
////    {
////        $query = User::find();
////        $users = new yii\data\ActiveDataProvider(['query' => $query]);
//////        $query->andFilterWhere(['like', 'username', 'weixi']);
////        $data = $users->getModels();
////        return $data;
////    }
//
//    public function actionSearch($username)
//    {
//        $params['UserSearch']['username'] = $username;
//        $userSearch = new UserSearch();
//        $provider = $userSearch->search($params);
//        $data = $provider->getModels();
//        return $data;
//    }


}  