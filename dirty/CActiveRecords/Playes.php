<?php

/**
 * This is the model class for table "playes".
 *
 * The followings are the available columns in table 'playes':
 * @property integer $id
 * @property string $token
 * @property string $username
 * @property string $login_mail
 * @property string $password_md5
 * @property string $gems
 * @property string $food
 * @property string $gold
 * @property integer $lands_count
 * @property string $status
 * @property string $lang
 */
class Playes extends CActiveRecord{

    const KEY_SCENARIO_UPDATE = 'update';

    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';

    const FIELD_PASSWORD_HASH = 'password_hash';
    const FIELD_PASSWORD      = 'password';
    const FIELD_LOGIN         = 'mail';
    const FIELD_STATUS        = 'status';
    const FIELD_TOKEN         = 'user_token';
    const FIELD_USERNAME      = 'username';
    const FIELD_MONEY         = 'gold';

	/**
	 * @return string the associated database table name
	 */
	public function tableName(){
		return 'playes';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
            array( Playes::FIELD_LOGIN . ',' . Playes::FIELD_PASSWORD_HASH , 'safe'     , 'on' => Playes::KEY_SCENARIO_UPDATE ),
            array( Playes::FIELD_LOGIN . ',' . Playes::FIELD_PASSWORD_HASH , 'required' , 'on' => Playes::KEY_SCENARIO_UPDATE ),
            array( Playes::FIELD_LOGIN , 'unique' , 'on' => Playes::KEY_SCENARIO_UPDATE ),
            array( Playes::FIELD_LOGIN , 'email' ),
		);

	}



	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_token' => 'Token',
			'username' => 'Username',
			'login_mail' => 'Login Mail',
			'password_md5' => 'Password Md5',
			'gems' => 'Gems',
			'food' => 'Food',
			'gold' => 'Gold',
			'lands_count' => 'Lands Count',
			'status' => 'Status',
			'lang' => 'Lang',
		);
	}


	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Playes the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    protected function afterSave()
    {
        parent::afterSave();
        $this->{Playes::FIELD_STATUS} = $this->{Playes::FIELD_STATUS} == Playes::STATUS_ON ? true : false ;
    }

    protected function afterFind()
    {
        parent::afterFind();
        $this->{Playes::FIELD_STATUS} = $this->{Playes::FIELD_STATUS} == Playes::STATUS_ON ? true : false ;
    }


    protected function beforeSave(){

        if(parent::beforeSave()){

            if($this->getScenario() == Playes::KEY_SCENARIO_UPDATE && $this->getIsNewRecord() ){

                $this->{Playes::FIELD_STATUS} = Playes::STATUS_ON ;
                $this->{Playes::FIELD_USERNAME} = array_shift(explode( '@' , $this->{Playes::FIELD_LOGIN} ));

                $hash = crc32($this->{Playes::FIELD_LOGIN}.'|'.$this->{Playes::FIELD_PASSWORD_HASH});

                $this->{Playes::FIELD_TOKEN} = hexdec( $hash );
            }

            return true;
        }
        else
            return false;
    }

}
