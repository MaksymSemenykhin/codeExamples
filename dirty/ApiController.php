<?php

/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Api extends CController {

    const GAME_STATUS_OK  = 'on';
    const GAME_STATUS_OFF = 'off';
    const GAME_STATUS_BEFORE_RESTART = 'restart';

    public $layout = 'empty';

    const KEY_USER_TOKEN = 'user_token';

    public static function getRullssets($key){
        return isset( self::$preRullssets[$key] ) ? self::$preRullssets[$key] : array( 'valid' => '' , 'filters' => '' );
    }

    public static $preRullssets = array(
        Playes::FIELD_LOGIN => array(
            'valid' => 'required|valid_email|max_len,20|min_len,3' ,
            'filters' => 'trim|sanitize_string' ,
        ) ,

        Playes::FIELD_USERNAME => array(
            'valid' => 'required|alpha_numeric|max_len,20|min_len,6' ,
            'filters' => 'trim|sanitize_string' ,
        ) ,

        Playes::FIELD_PASSWORD => array(
            'valid' => 'required|max_len,20|min_len,6' ,
            'filters' => 'trim|sanitize_string' ,
        ) ,
        self::KEY_USER_TOKEN => array(
            'valid' => 'required|alpha_numeric' ,
            'filters' => 'trim|sanitize_string' ,
        ) ,

        Planet::FIELD_PASSWORD => array(
            'valid' => 'max_len,20|min_len,6' ,
            'filters' => 'trim|sanitize_string' ,
        ) ,
        Planet::FIELD_SIZE => array(
            'valid' => 'max_len,3|min_len,1|integer' ,
        ) ,
        Planet::FIELD_LAND_TYPE => array(
            'valid' => 'enum,common,mother' ,
        ) ,
        Planet::FIELD_BIOM => array(
            'valid' => 'enum,common' ,
        ) ,
        Planet::FIELD_FRQ => array(
            'valid' => 'max_len,3|min_len,1|integer' ,
        ) ,
        Planet::FIELD_OCTAVES => array(
            'valid' => 'max_len,3|min_len,1|integer' ,
        ) ,

        Unit::FIELD_TYPE => array(
            'valid' => 'enum,engineer,intel|required'
        )

    );
    protected $user_token = FALSE;
    protected $user_data = FALSE;
    protected $form = array();
    protected $cache = array();
    protected $location = FALSE;


    public function location() {
        return $this->location;
    }

    public function init() {

        $this->location = Yii::app()->Location;
        parent::init();

        Yii::app()->attachEventHandler( 'onError' , array( $this , 'handleError' ) );
        Yii::app()->attachEventHandler( 'onException' , array( $this , 'handleError' ) );


        foreach ( $this->form as $key => $value ) {
            unset($this->form[ $key ]);
            $key                = strtolower( $key );
            $this->form[ $key ] = $value;
        }

        foreach ( $this->cache as $key => $value ) {
            unset($this->cache[ $key ]);
            $key                 = strtolower( $key );
            $this->cache[ $key ] = $value;
        }

    }

    public function handleError( CEvent $event ) {

        if($event instanceof CExceptionEvent)
        {
            $body = array(
                'code' => $event->exception->getCode(),
                'message' => $event->exception->getMessage(),
                'file' => $event->exception->getFile(),
                'line' => $event->exception->getLine()
            );
        }
        else
        {
            $body = array(
                'code' => $event->code,
                'message' => $event->message,
                'file' => $event->file,
                'line' => $event->line
            );

        }
        $event->handled = true;
        if(YII_DEBUG){
            $body['trace'] = $event->sender->components['log']->getRoutes();
            CVarDumper::dump($body , 10 , true);

        }else{
            $email = Yii::app()->getComponents(false);
            $email = $email['email'];
            if($email['enabled']){

                $subject = array();
                $subject[] = Yii::app()->params['domain'];
                $subject[1] = Yii::app()->name;

                $subject[2] = '';
                foreach ($event->sender->components['log']->getRoutes() as $key => $value) {
                    $subject[2].=$value->levels.';';
                }
                $subject = implode(' | ',$subject);
                $to = implode(',', Yii::app()->params['adminEmails']);
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                 mail($to, $subject, CVarDumper::dumpAsString($body , 10 , true) , $headers);

            }
            $this->endNotOkText( 'exaptation' );
        }
        exit;
    }



    protected function beforeAction( $action ) {

        $this->postDataCheck( 'action' . $action->controller->action->id );

        if ( isset($_REQUEST[ Api::KEY_USER_TOKEN ]) ) {

            $result = Playes::model()->findByAttributes( array(
                                                             'user_token' => Yii::app()->request->getParam( Api::KEY_USER_TOKEN , FALSE ) ,
                                                         ) );
            if ( empty($result->attributes) ) {
                $this->endNotOkText( 'wrong_user_token' );
            }

            $this->user_data = $result->attributes;
            $this->location  = Yii::app()->Location;
            $this->location->load( $result->attributes[ 'lang' ] );

        }

        $value = FALSE;
        if ( isset($this->cache[ strtolower( 'action' . Yii::app()->controller->action->id ) ]) ) {
            $key   = serialize( $_REQUEST ) . serialize( $_SERVER[ 'REQUEST_URI' ].YII_DEBUG );
            $value = Yii::app()->cache->get( $key );
        }

        if ( $value !== FALSE ) {
            $value = json_decode($value , true);
            $value['dev'] = array(
                'cache' => true,
            );

            $value = json_encode($value);

            if ( isset($_REQUEST[ 'html' ]) ) {
                echo format_json( $value , TRUE );
                exit;
            }
            echo $value;
            exit;
        }

        return parent::beforeAction( $action );
    }

    public function postDataCheck( $action ) {


        $gump = Yii::app()->gump;
        if ( isset($this->form[ $action ]) && !empty($this->form[ $action ]) && empty($_REQUEST) ) {
            $this->endNotOk( array(
                                 'validation_response' => 'empty post'
                             ) );
        }

        if ( isset($this->form[ $action ]) ) {

            if ( isset($this->form[ $action ]) ) {
                $valids  = array();
                $filters = array();
                foreach ( $this->form[ $action ] as $key => $value ) {
                    if(!isset( Api::$preRullssets[ $value ])){
                        continue;
                    }
                    $valids[ $value ]  = Api::$preRullssets[ $value ][ 'valid' ];
                    if(isset(Api::$preRullssets[ $value ][ 'filters' ])){
                        $filters[ $value ] = Api::$preRullssets[ $value ][ 'filters' ];
                    }
                }

                $validated = $gump->validate( $_REQUEST , $valids );
                $_REQUEST  = $gump->filter( $_REQUEST , $filters );

            }


            if ( TRUE !== $validated )
                $this->endNotOk( array(
                                     'validation_response' => $validated
                                 ) );

        }

    }


    public function endOk( $data = NULL , $text = FALSE ) {

        $this->end( 1 , $data , $text );

    }

    public function endNotOk( $data = NULL , $text = FALSE ) {

        $this->end( 0 , $data , $text );

    }

    public function endNotOkText( $text = FALSE ) {

        $this->endNotOk( NULL , $text );

    }

    public function end( $status , $data , $text = FALSE ) {

        //cutting all denied fields, data from  json response
        if ( is_array( $data ) && !empty($data) ) {
            foreach ( $data as $key => $value ) {
                if ( is_array( $value ) ) {
                    $data[ $key ] = $this->cutDenide( $value );
                }

            }
            $data = $this->cutDenide( $data );
        }

        $js   = FALSE;
        $cacheTime = -1;
        $key2 = strtolower( 'action' . Yii::app()->controller->action->id );
        if ( isset($this->cache[ $key2 ]) ) {
            $cacheTime = $this->cache[ $key2 ];
            $key       = serialize( $_REQUEST ) . serialize( $_SERVER[ 'REQUEST_URI' ].YII_DEBUG );
            $js        = Yii::app()->cache->get( $key );
        }

        if ( $js === FALSE ) {
            $data = array( 'status' => $status , 'data' => $data ) ;
            if(YII_DEBUG){
                $data['dev'] = array(
                    'ExecutionTime' => Yii::getLogger()->getExecutionTime(),
                );
            }
            $js = json_encode( $data );
            if ( $cacheTime > -1 ) {
                Yii::app()->cache->set( $key , $js , $cacheTime );
            }

        }
        if ( isset($_REQUEST[ 'html' ]) ) {
            echo CVarDumper::dump( $data ,10 , TRUE );
            exit;
        }
        echo $js;
        exit;
    }

    public function cutDenide( $row ) {

        $denied = array(
            'login_mail' ,
            'password_md5' ,
            'user_id' ,
            'game_x' ,
            'game_y' ,
            'land_id' ,

            'solar_id' ,
            'galaxy_id' ,
            'sector_id',

            'private_registration' ,
            'private_first_login' ,

            'life_time_left_reduction',

        );
        foreach ( $denied as $key => $value ) {
            foreach ( $row as $key2 => $value2 ) {
                if ( is_array( $value2 ) )
                    $row[ $key2 ] = $this->cutDenide( $value2 );

                if ( isset($row[ $value ]) )
                    unset($row[ $value ]);
            }
        }

        return $row;
    }
}