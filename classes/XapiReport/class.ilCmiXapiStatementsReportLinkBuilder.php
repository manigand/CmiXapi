<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilCmiXapiStatmentsAggregateLinkBuilder
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 * @package     Module/CmiXapi
 */
class ilCmiXapiStatementsReportLinkBuilder extends ilCmiXapiAbstractReportLinkBuilder
{
    /**
     * @return array
     */
    protected function buildPipeline() : array
    {
    ilObjCmiXapi::log()->debug('Dans buildPipeline');
        $pipeline = array();
        $obj = $this->getObj();
        
        //$pipeline[] = $this->buildFilterStage();

        //$pipeline[] = $this->buildOrderingStage();
        /*
        $pipeline[] = array('$facet' => array(
            'stage1' => array(
                array('$group' => array('_id' => null, 'count' => array('$sum' => 1) ))
            ),
            'stage2' => $this->buildLimitStage()
        ));*/
        
        //$pipeline[] = array('$unwind' => '$stage1');
        /*
        $pipeline[] = array('$project' => array(
                'maxcount' => '$stage1.count',
                'statements' => '$stage2.statement'
        ));*/
        
        ilObjCmiXapi::log()->debug('Limit : '.$this->filter->getLimit());
        ilObjCmiXapi::log()->debug('Offset : '.$this->filter->getOffset());
        /*
        ilObjCmiXapi::log()->debug('ActivityId : '.$this->buildActivityId());
        ilObjCmiXapi::log()->debug('verb : '.$this->filter->getVerb());
        ilObjCmiXapi::log()->debug('start : '.$this->filter->getStartDate());
        ilObjCmiXapi::log()->debug('end : '.$this->filter->getEndDate());
        */
        $log = ilLoggerFactory::getLogger('cmix');
        //$log->debug("aggregation pipeline:\n" . json_encode($pipeline, JSON_PRETTY_PRINT));
        ilObjCmiXapi::log()->debug('ordre de tri : '.$this->orderingField().'|'.$this->filter->getOrderDirection());
        $params='activity='.$obj->getActivityId();
        if($this->filter->getVerb()!=''){
        	$params.='&verb='.$this->filter->getVerb();
        }
        if($this->filter->getStartDate() || $this->filter->getEndDate()) {          
            if ($this->filter->getStartDate()) {
                $params.='&since='.$this->filter->getStartDate()->toXapiTimestamp();
            }
            if ($this->filter->getEndDate()) {
                $params.='&until='.$this->filter->getEndDate()->toXapiTimestamp();
            }
        }
        if ($this->filter->getActor()){
        
        	if($obj->getContentType() == ilObjCmiXapi::CONT_TYPE_CMI5){
        	  	$params.='&agent={"account":{"homePage":"http://'.str_replace('www.', '', $_SERVER['HTTP_HOST']).'","name":"'.$this->filter->getActor()->getUsrIdent().'"}}';
        	}
        	else{
        		$params.='&agent={"mbox":"mailto:'.$this->filter->getActor()->getUsrIdent().'"}';
        	}
        }
        if ($this->orderingField()=='dateAsc'){$params.='&ascending=true';}
        
        
        $pipeline=array($params.'&related_activities='.$this->buildRelatedActivities().'&limit=0');
        return $pipeline;
    }
    // modification mgd
    protected function buildActivityId()
    {
    	$obj = $this->getObj();
    	return $obj->getActivityId();
    }
    protected function buildRelatedActivities()
    {
    	return 'true';
    }
    public function orderingField(){
    ilObjCmiXapi::log()->debug('Dans OrderingFields');
    switch ($this->filter->getOrderField()) {
            case 'object': // definition/description are displayed in the Table if not empty => sorting not alphabetical on displayed fields
                ilObjCmiXapi::log()->debug('tri par objet');
                $column = 'objet';
                ilUtil::sendInfo("Le tri par $column n'est pas disponible");
                break;
                
            case 'verb':
            ilObjCmiXapi::log()->debug('tri par verbe');
                $column = 'verbe';
                ilUtil::sendInfo("Le tri par $column n'est pas disponible");
                break;
                
            case 'actor':
            ilObjCmiXapi::log()->debug('tri par acteur');
                $column = 'utilisateur';
                ilUtil::sendInfo("Le tri par $column n'est pas disponible");
                break;
                
            case 'date':
            	ilObjCmiXapi::log()->debug('tri par date');
            	if ($this->filter->getOrderDirection()=='asc'){
            	    	$column='dateAsc';
            	    	}
            	else {$column='dateDesc';}
            	break;
            default:
            ilObjCmiXapi::log()->debug('tri par defaut');
                $column = 'dateDesc';
                break;
        }
        
        return $column;
    }
    // fin modif
    
    protected function buildLimitStage()
    {/*
        $stage = array(
            array('$skip' => (int) $this->filter->getOffset())
        );
        
        if ($this->filter->getLimit()) {
            $stage[] = array('$limit' => (int) $this->filter->getLimit());
        }
        return $stage;*/
    }
    
    protected function buildFilterStage()
    {
    /*
    ilObjCmiXapi::log()->debug('Dans buildFilterStage');
        $cmi5_extensions_query = false;

        $stage = array();
        $stage['statement.object.objectType'] = 'Activity';
        $stage['statement.actor.objectType'] = 'Agent';
        if ($this->filter->getVerb()) {
            $stage['statement.verb.id'] = $this->filter->getVerb();
        }
        
        if ($this->filter->getStartDate() || $this->filter->getEndDate()) {
            $stage['statement.timestamp'] = array();
            
            if ($this->filter->getStartDate()) {
                $stage['statement.timestamp']['$gt'] = $this->filter->getStartDate()->toXapiTimestamp();
            }
            
            if ($this->filter->getEndDate()) {
                $stage['statement.timestamp']['$lt'] = $this->filter->getEndDate()->toXapiTimestamp();
            }
        }

        $obj = $this->getObj();
        $activityId = array();

        if ($cmi5_extensions_query == true && $obj->getContentType() == ilObjCmiXapi::CONT_TYPE_CMI5 && !$obj->isMixedContentType()) {
            // https://github.com/AICC/CMI-5_Spec_Current/blob/quartz/cmi5_spec.md#963-extensions
            $activityId['statement.context.extensions.https://ilias&46;de/cmi5/activityid'] = $obj->getActivityId();
        } else {
            // for case-insensive: '$regex' => '(?i)^' . preg_quote($this->filter->getActivityId()) . ''
            $activityQuery = [
                '$regex' => '^' . preg_quote($this->filter->getActivityId()) . ''
            ];
            $activityId['$or'] = [];
            // ToDo : restriction to exact activityId?
            // query existing activityId in grouping? we have not enough control over acticityId in xapi statements
            // another way put the obj_id into a generated registration, but we are not sure that content will put this into statement context
            // $activityId['$or'][] = ['statement.object.id' => "{$this->filter->getActivityId()}"];
            $activityId['$or'][] = ['statement.object.id' => $activityQuery];
            $activityId['$or'][] = ['statement.context.contextActivities.parent.id' => $activityQuery];
            $activityId['$or'][] = ['statement.context.contextActivities.grouping.id' => $activityQuery];
        }

        $actor = array();
        
        // mixed
        if ($obj instanceof ilObjCmiXapi && $obj->isMixedContentType()) {
            if ($this->filter->getActor()) {
                // could be registration query but so what...
                foreach (ilCmiXapiUser::getUserIdents($this->getObjId(), $this->filter->getActor()->getUsrId()) as $usrIdent) {
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"]; // older statements
                    $actor['$or'][] = ['statement.actor.account.name' => "{$usrIdent}"];
                }
                // not launched yet?
                if (count($actor) == 0) {
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$this->filter->getActor()->getUsrIdent()}"]; // older statements
                    $actor['$or'][] = ['statement.actor.account.name' => "{$this->filter->getActor()->getUsrIdent()}"];
                }
            } else {
                $actor['$or'] = [];
                foreach (ilCmiXapiUser::getUsersForObject($this->getObjId()) as $cmixUser) {
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
                    $actor['$or'][] = ['statement.actor.account.name' => "{$cmixUser->getUsrIdent()}"];
                }
            }
        } elseif ($obj instanceof ilObjCmiXapi && $obj->getContentType() == ilObjCmiXapi::CONT_TYPE_CMI5) {
            if ($this->filter->getActor()) {
                $cmixUser = $this->filter->getActor();
                $actor['statement.context.registration'] = $cmixUser->getRegistration();
            }
        } else {
            if ($this->filter->getActor()) {
                foreach (ilCmiXapiUser::getUserIdents($this->getObjId(), $this->filter->getActor()->getUsrId()) as $usrIdent) {
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                }
                // not launched yet?
                if (count($actor) == 0) {
                    $actor['statement.actor.mbox'] = $this->filter->getActor()->getUsrIdent();
                }
            }
            /**
             * i don't think this will work with user >~ 100
             * this will blow up the GET request
             * GET Queries are sometimes limited to an amount of characters
             */
             /*
            else {
                $actor['$or'] = [];
                foreach (ilCmiXapiUser::getUsersForObject($this->getObjId()) as $cmixUser) {
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
                }
            }
        }
        $stage['$and'] = [];
        $stage['$and'][] = $activityId;
        if (count($actor) > 0) {
            $stage['$and'][] = $actor;
        }
        return array('$match' => $stage);
        */
    }
    
    protected function buildOrderingStage()
    {
    /*
        $obj = $this->getObj();
        $actor = '';
        if ($obj instanceof ilObjLTIConsumer) {
            if ($obj->getProvider()->getPrivacyName() != ilObjCmiXapi::PRIVACY_NAME_NONE) {
                $actor = 'statement.actor.name';
            }
        } else {
            if ($obj->getPrivacyName() != ilObjCmiXapi::PRIVACY_NAME_NONE) {
                $actor = 'statement.actor.name';
            } else {
                if ($obj->getContentType() == ilObjCmiXapi::CONT_TYPE_CMI5) {
                    if ($obj->getPublisherId() == '') { // old
                        $actor = 'statement.actor.mbox';
                    } else {
                        $actor = 'statement.actor.account.name';
                    }
                } else {
                    $actor = 'statement.actor.mbox';
                }
            }
        }
        switch ($this->filter->getOrderField()) {
            case 'object': // definition/description are displayed in the Table if not empty => sorting not alphabetical on displayed fields
                ilObjCmiXapi::log()->debug('tri par objet');
                $field = 'statement.object.id';
                break;
                
            case 'verb':
            ilObjCmiXapi::log()->debug('tri par verbe');
                $field = 'statement.verb.id';
                break;
                
            case 'actor':
            ilObjCmiXapi::log()->debug('tri par acteur');
                $field = $actor;
                break;
                
            case 'date':
            	ilObjCmiXapi::log()->debug('tri par date');
            	break;
            default:
            ilObjCmiXapi::log()->debug('tri par defaut');
                $field = 'statement.timestamp';
                break;
        }
        
        $orderingFields = array(
            $field => $this->filter->getOrderDirection() == 'desc' ? -1 : 1
        );
        
        return array('$sort' => $orderingFields);
        */
    }
}
