<?php

namespace app\controllers;

use app\components\BaseController;
use app\models\User;
use Yii;
use yii\db\Query;
use yii\web\Response;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\base\InvalidArgumentException;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\SignupForm;
use app\models\Contest;
use app\models\Discuss;
use app\models\ResendVerificationEmailForm;
use app\models\PasswordResetRequestForm;
use app\models\ResetPasswordForm;
use app\models\VerifyEmailForm;

class SiteController extends BaseController
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'fontFile' => '@webroot/fonts/Astarisborn.TTF',
                'width' => 180
            ],
        ];
    }

    public function actionConstruction()
    {
        return $this->render('construction');
    }

    public function actionNews($id)
    {
        $model = Discuss::find()->where(['id' => $id, 'status' => Discuss::STATUS_PUBLIC, 'entity' => Discuss::ENTITY_NEWS])->one();

        if ($model === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $this->render('news', [
            'model' => $model
        ]);
    }

    public function actionIndex()
    {
        $contests = Yii::$app->db->createCommand('
            SELECT id, title FROM {{%contest}}
            WHERE status = :status AND type != :type AND end_time >= :time
            ORDER BY start_time DESC LIMIT 3
        ', [':status' => Contest::STATUS_VISIBLE, ':type' => Contest::TYPE_HOMEWORK, ':time' => date('Y:m:d h:i:s', time())])->queryAll();

        $newsQuery = (new Query())->select('id, title, content, created_at')
            ->from('{{%discuss}}')
            ->where(['entity' => Discuss::ENTITY_NEWS, 'status' => Discuss::STATUS_PUBLIC])
            ->orderBy('id DESC');

        $discusses = (new Query())->select('d.id, d.title, d.created_at, u.nickname, u.username, p.title as ptitle, p.id as pid')
            ->from('{{%discuss}} as d')
            ->leftJoin('{{%user}} as u', 'd.created_by=u.id')
            ->leftJoin('{{%problem}} as p', 'd.entity_id=p.id')
            ->where(['entity' => Discuss::ENTITY_PROBLEM, 'parent_id' => 0])
            ->andWhere('DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= date(d.updated_at)')
            ->orderBy('d.updated_at DESC')
            ->limit(10)
            ->all();

        $pages = new Pagination(['totalCount' => $newsQuery->count()]);
        $news = $newsQuery->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        return $this->render('index', [
            'contests' => $contests,
            'pages' => $pages,
            'news' => $news,
            'discusses' => $discusses
        ]);
    }

    public function actionPrint()
    {
        return $this->render('print');
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if (Yii::$app->session->get('attempts-login') > 2) {
            $model->scenario = 'withCaptcha';
        }
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {
                $status = $model->getUser()->status;
                if ($status == User::STATUS_INACTIVE) {
                    $url = Yii::$app->urlManager->createAbsoluteUrl(['/site/resend-verification-email']);
                    $a = "<a href=\"$url\">$url</a>";
                    Yii::$app->session->setFlash('error', 'T??i kho???n ch??a ???????c k??ch ho???t v?? kh??ng th??? ????ng nh???p. Vui l??ng x??c minh email: ' . $a);
                } else if ($status == User::STATUS_DISABLE) {
                    Yii::$app->session->setFlash('error', 'T??i kho???n n??y ???? b??? v?? hi???u h??a');
                }
                return $this->goBack();
            } else {
                Yii::$app->session->set('attempts-login', Yii::$app->session->get('attempts-login', 0) + 1);
                if (Yii::$app->session->get('attempts-login') > 2) {
                    $model->scenario = 'withCaptcha';
                }
            }
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionSignup()
    {
        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->setting->get('mustVerifyEmail')) {
                    $url = Yii::$app->urlManager->createAbsoluteUrl(['/site/resend-verification-email']);
                    $a = "<a href=\"$url\">Kh??ng nh???n ???????c email? </a>";
                    Yii::$app->session->setFlash('success', 'Vui l??ng ki???m tra h???p th?? ?????n ????? x??c minh email c???a b???n. ' . $a);
                    return $this->goHome();
                }
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Li??n k???t ?????t l???i m???t kh???u ???? ???????c g???i ?????n email c???a b???n. Vui l??ng ki???m tra email ????? ?????t l???i m???t kh???u. ');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Kh??ng th??? g???i li??n k???t ?????t l???i m???t kh???u. ');
            }
        }

        return $this->render('request_password_reset_token', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', '???? l??u m???t kh???u m???i.');

            return $this->goHome();
        }

        return $this->render('reset_password', [
            'model' => $model,
        ]);
    }

    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            Yii::$app->session->setFlash('success', 'Email c???a b???n ???? ???????c x??c th???c');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Xin l???i, t??i kho???n c???a b???n kh??ng th??? x??c th???c b???ng m?? ???? nh???p.');
        return $this->goHome();
    }

    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Ki???m tra email c???a b???n ????? l???y li??n k???t x??c th???c');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Kh??ng th??? g???i email x??c th???c. ');
        }

        return $this->render('resend_verification_email', [
            'model' => $model
        ]);
    }
}
