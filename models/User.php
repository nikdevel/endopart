<?php

namespace app\models;

use phpDocumentor\Reflection\Types\This;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\rbac\Role;
use yii\web\IdentityInterface;

/**
 * Модель пользователя User
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $structure_id
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 * @property string $new_password
 * @property string $user_role
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const SCENARIO_CREATE_USER = 'create_user';

    public $new_password;
    public $user_role;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            [['fullname', 'address', 'phone', ], 'string' ],
            [['fullname', 'address', 'phone', 'structure_id', ], 'safe' ],
            ['user_role', 'required'],
            ['user_role', 'in' , 'range' => ArrayHelper::getColumn(Yii::$app->authManager->getRoles(), 'name')],
            [['new_password', 'username', ], 'required', 'on' => self::SCENARIO_CREATE_USER ],
        ];
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels['username'] = 'Логин';
        $labels['fullname'] = 'ФИО';
        $labels['address'] = 'Адрес';
        $labels['phone'] = 'Телефон';
        $labels['structure_id'] = 'Отдел';
        $labels['created_at'] = 'Время создания';
        $labels['updated_at'] = 'Время обновления';
        $labels['new_password'] = 'Пароль';
        $labels['user_role'] = 'Права';
        return $labels;
    }

    /**
     * Преобразование форматов данных для отображения
     * @return void
     */
    public function afterFind()
    {
        $this->updated_at = date('d.m.y H:m:i',$this->updated_at);
        $this->created_at = date('d.m.y H:m:i',$this->created_at);
//        var_dump(Yii::$app->authManager->getRolesByUser($this->getId());die;
        $this->user_role = array_key_first(Yii::$app->authManager->getRolesByUser($this->getId())) ;
        parent::afterFind(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Поиск пользователя по имени
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Валидация пароля
     *
     * @param string $password пароль для валидации
     * @return bool если пароль подходит пользователю
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Генерирует хэш пароля для записи в базу данных
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Генерирует ключ для "Запомнить меня"
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Для корректной записи в БД структуры пользователя, если её нет
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($this->structure_id === '') {
            $this->structure_id = null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * Редактирование прав пользователя, если они были изменены
     * @param $insert
     * @param $changedAttributes
     * @return void
     * @throws \Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        $authManager = Yii::$app->authManager;
        if ($authManager->getRolesByUser($this->getId()) != $authManager->getRole($this->user_role)) {
            $authManager->revokeAll($this->getId());
            $role = $authManager->getRole($this->user_role);
            $authManager->assign($role, $this->getId());
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Создает связь один к одному с моделью Структура
     * @return \yii\db\ActiveQuery
     */
    public function getStructure()
    {
        return $this->hasOne(Structure::className(),[ 'id' => 'structure_id']);
    }

}