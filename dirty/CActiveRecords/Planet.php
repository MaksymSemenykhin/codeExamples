<?php

class Planet extends CActiveRecord {

    const TTL_END    = 7;
    const TTL_OLD    = 30;
    const TTL_NORMAL = 90;
    const TTL_START  = 200;

    const LAND_BIOM_COMMON = 'common';

    const FIELD_PASSWORD      = 'password_hash';
    const FIELD_SIZE          = 'size';
    const FIELD_LAND_TYPE     = 'land_type';
    const FIELD_FRQ           = 'frq';
    const FIELD_OCTAVES       = 'octaves';
    const FIELD_BIOM          = 'biom';
    const FIELD_SOLAR_ID      = 'solar_id';
    const FIELD_GALAXY_ID     = 'galaxy_id';
    const FIELD_GALAXY_SECTOR_ID  = 'sector_id';


    public function tableName() {
        return 'planet';
    }

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    protected function afterFind(){

        if($this->{Galaxy::FIELD_LIFE_TIME} < Galaxy::TTL_END*1*60*24 ){
            $this->{Galaxy::FIELD_LIFE_TIME} = 'умерающая';
        }else{
            if( $this->{Galaxy::FIELD_LIFE_TIME} < Galaxy::TTL_OLD*1*60*24 ){
                $this->{Galaxy::FIELD_LIFE_TIME} = 'старая';
            }else{
                if( $this->{Galaxy::FIELD_LIFE_TIME} < Galaxy::TTL_NORMAL*1*60*24  ){
                    $this->{Galaxy::FIELD_LIFE_TIME} = 'взрослая';
                }else{
                    $this->{Galaxy::FIELD_LIFE_TIME} = 'молодая';
                }

            }

        }

    }

    protected function beforeSave(){

        if(parent::beforeSave()){

            if( $this->getIsNewRecord() ){

                $connection = Yii::app()->db;
                $distans = 5;
                $distans_pow = pow($distans,$distans);
                $sql='SELECT id FROM '.$this->tableName().'
where

position_x > :x-:distans and position_x < :x+:distans and
position_y > :y-:distans and position_y < :y+:distans and
position_z > :z-:distans and position_z < :z+:distans  ';

                $sql = str_replace(':distans',$distans , $sql);
                $id = true;
                $count = 100;
                while($id){
                    $count--;

                    if($count<0){
                        break;
                    }
                    $bioms = array('common','gaGiant','asteroid','ice','jupiter','mars','mercury','neptune','probe','uranus','venase');
                    $this->{Planet::FIELD_BIOM} = $bioms[rand(0,count($bioms)-1)];
                    $this->position_x = rand(-$distans_pow,$distans_pow);
                    $this->position_y = rand(-$distans_pow,$distans_pow);
                    $this->position_z = rand(-$distans_pow,$distans_pow);
                    if(
                        ($this->position_x < 50 && $this->position_x > -50)||
                        ($this->position_y < 50 && $this->position_y > -50)||
                        ($this->position_z < 50 && $this->position_z > -50)
                    ){
                        continue;
                    }

                    $sql = str_replace(':x',$this->position_x , $sql);
                    $sql = str_replace(':y',$this->position_y , $sql);
                    $sql = str_replace(':z',$this->position_z , $sql);
                    $command=$connection->createCommand($sql);
                    $id = $command->queryScalar();

                }




            }

            return true;
        }
        else
            return false;
    }

}