<?php

namespace App\Pages;

use App\Entity\Pos;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\Form\File;

//POS терминалы
class PosList extends \App\Pages\Base
{
    private $_pos;
    private $_blist;

    public function __construct() {
        parent::__construct();
        if (System::getUser()->rolename != 'admins') {
            System::setErrorMsg("До сторінки має доступ тільки користувачі з роллю admins  ");
            \App\Application::RedirectError();
            return false;
        }
        $this->_blist = \App\Entity\Branch::getList(\App\System::getUser()->user_id);

        $this->add(new Panel('postable'))->setVisible(true);
        $this->postable->add(new DataView('poslist', new \ZCL\DB\EntityDataSource('\App\Entity\Pos', '', ' pos_name asc'), $this, 'poslistOnRow'))->Reload();
        $this->postable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('posdetail'))->setVisible(false);
        $this->posdetail->add(new DropDownChoice('editbranch', $this->_blist, 0));
        $this->posdetail->add(new DropDownChoice('editcomp', \App\Entity\Firm::getList(), 0));

        $this->posdetail->add(new TextInput('editpos_name'));

        $this->posdetail->add(new CheckBox('edittesting'));
        $this->posdetail->add(new CheckBox('editusefisc'));
        $this->posdetail->add(new TextInput('editposinner'));
        $this->posdetail->add(new TextInput('editfisc'));
        $this->posdetail->add(new TextInput('editfiscalnumber'));
        $this->posdetail->add(new TextInput('editaddress'));
        $this->posdetail->add(new TextInput('editpointname'));
        $this->posdetail->add(new TextInput('editcbpin'));
        $this->posdetail->add(new TextInput('editcbkey'));
        $this->posdetail->add(new TextInput('editvktoken'));
        $this->posdetail->add(new DropDownChoice('editautoshift'));
        $this->posdetail->add(new TextArea('editcomment'));

        $this->posdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->posdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        
        $this->add(new Form('keyform'))->setVisible(false);
        $this->keyform->add(new SubmitButton('send'))->onClick($this, 'onSend')  ;
        $this->keyform->add(new Button('cancelppo'))->onClick($this, 'cancelOnClick');
        $this->keyform->add(new Button('delppo'))->onClick($this, 'delOnClick');
        $this->keyform->add(new TextInput('password'));
        $this->keyform->add(new DropDownChoice('signtype', 0))->onChange($this, 'onSignType');
        $this->keyform->signtype->setVisible(false);
        $this->keyform->add(new TextInput('serhost'));
        $this->keyform->add(new TextInput('serport'));
        $this->keyform->add(new CheckBox('outher'));
        $this->keyform->add(new CheckBox('usessl'));
        $this->keyform->add(new File('keyfile'));
        $this->keyform->add(new File('certfile'));
        
        
        $modules = System::getOptions('modules');

        $this->_tvars["loadkey"] = ($modules['ppo'] == 1 || $modules['vdoc'] == 1 ) ;
        
    }

    public function poslistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('pos_name', $item->pos_name));
        $row->add(new Label('branch_name', $this->_blist[$item->branch_id]??''));
        $row->add(new Label('comment', $item->comment));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        
        $row->add(new Label('ppoowner', $item->ppoowner));
        $row->add(new Label('ppokeyid', $item->ppokeyid));

        $row->add(new ClickLink('ppo'))->onClick($this, 'ppoOnClick');
        
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('PosList')) {
            return;
        }
        $pos = $sender->owner->getDataItem();

        $del = Pos::delete($pos->pos_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->postable->poslist->Reload();
    }

    public function editOnClick($sender) {
        $this->_pos = $sender->owner->getDataItem();
        $this->postable->setVisible(false);
        $this->posdetail->setVisible(true);
        $this->posdetail->editpos_name->setText($this->_pos->pos_name);
        $this->posdetail->editbranch->setValue($this->_pos->branch_id);
        $this->posdetail->editcomp->setValue($this->_pos->firm_id);
        $this->posdetail->editaddress->setText($this->_pos->address);
        $this->posdetail->editpointname->setText($this->_pos->pointname);
        $this->posdetail->editvktoken->setText($this->_pos->vktoken);
        $this->posdetail->editcbkey->setText($this->_pos->cbkey);
        $this->posdetail->editcbpin->setText($this->_pos->cbpin);
        $this->posdetail->editautoshift->setValue($this->_pos->autoshift);
        $this->posdetail->editposinner->setText($this->_pos->fiscallocnumber);
        $this->posdetail->editfisc->setText($this->_pos->fiscalnumber);
        $this->posdetail->editfiscalnumber->setText($this->_pos->fiscdocnumber);
        $this->posdetail->edittesting->setChecked($this->_pos->testing);
        $this->posdetail->editusefisc->setChecked($this->_pos->usefisc);

        $this->posdetail->editcomment->setText($this->_pos->comment);
    }

    public function addOnClick($sender) {
        $this->postable->setVisible(false);
        $this->posdetail->setVisible(true);
        // Очищаем  форму
        $this->posdetail->clean();
        $b = \App\System::getBranch();
        $this->posdetail->editbranch->setValue($b > 0 ? $b : 0);
        $this->_pos = new Pos();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('PosList')) {
            return;
        }


        $this->_pos->pos_name = $this->posdetail->editpos_name->getText();

        $this->_pos->branch_id = $this->posdetail->editbranch->getValue();
        $this->_pos->firm_id = $this->posdetail->editcomp->getValue();

        $this->_pos->address = $this->posdetail->editaddress->getText();
        $this->_pos->pointname = $this->posdetail->editpointname->getText();
        $this->_pos->vktoken = $this->posdetail->editvktoken->getText();
        $this->_pos->cbkey = $this->posdetail->editcbkey->getText();
        $this->_pos->cbpin = $this->posdetail->editcbpin->getText();
        $this->_pos->autoshift = $this->posdetail->editautoshift->getValue();
        $this->_pos->fiscallocnumber = $this->posdetail->editposinner->getText();
        $this->_pos->fiscalnumber = $this->posdetail->editfisc->getText();
        $this->_pos->fiscdocnumber = $this->posdetail->editfiscalnumber->getText();
        $this->_pos->testing = $this->posdetail->edittesting->isChecked() ? 1 : 0;
        $this->_pos->usefisc = $this->posdetail->editusefisc->isChecked() ? 1 : 0;

        if ($this->_pos->pos_name == '') {
            $this->setError("Не введено назву");
            return;
        }
        if ($this->_pos->firm_id == 0) {
            $this->setError("Не обрано компанію");
            return;
        }
        if ($this->_tvars['usebranch'] == true && $this->_pos->branch_id == 0) {

            $this->setError("Виберіть філію");
            return;
        }
        $fn = intval($this->_pos->fiscdocnumber);

        if(($fn >0) == false) {
            $this->_pos->fiscdocnumber = 1;
        }

        $this->_pos->comment = $this->posdetail->editcomment->getText();

        $this->_pos->save();
        $this->posdetail->setVisible(false);
        $this->postable->setVisible(true);
        $this->postable->poslist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->postable->setVisible(true);
        $this->posdetail->setVisible(false);
        $this->keyform->setVisible(false);
    }

    //PPO
    public function ppoOnClick($sender) {

        $this->_pos = $sender->owner->getDataItem();
        $this->keyform->setVisible(true);
        $this->postable->setVisible(false);
        $this->keyform->password->setText('') ;
        $this->keyform->serhost->setText($this->_pos->ppohost) ;
        $this->keyform->serport->setText($this->_pos->ppoport) ;
        $this->keyform->usessl->setChecked($this->_pos->ppousessl) ;

        $this->keyform->delppo->setVisible(strlen($this->_pos->ppoowner)>0) ;

        $this->keyform->signtype->setValue($this->_pos->pposigntype) ;

        $this->onSignType($this->keyform->signtype);

    }
    public function delOnClick($sender) {

        $this->_pos->ppoowner =  ''  ;
        $this->_pos->ppocert = ''  ;
        $this->_pos->ppokey =  ''  ;
        $this->_pos->ppokeyid =  ''  ;
        $this->_pos->save();
        $this->postable->setVisible(true);
        $this->posdetail->setVisible(false);
        $this->keyform->setVisible(false);
        $this->postable->poslist->Reload();

    }

    public function onSignType($sender) {
        $this->keyform->password->setVisible(false);
        $this->keyform->keyfile->setVisible(false);
        $this->keyform->certfile->setVisible(false);
        $this->keyform->serhost->setVisible(false);
        $this->keyform->serport->setVisible(false);
        $this->keyform->usessl->setVisible(false);

        if($sender->getValue()==0) {
            $this->keyform->password->setVisible(true);
            $this->keyform->keyfile->setVisible(true);
            $this->keyform->certfile->setVisible(true);

        }
        if($sender->getValue()==1) {
            $this->keyform->serhost->setVisible(true);
            $this->keyform->serport->setVisible(true);
            $this->keyform->usessl->setVisible(true);
            $this->keyform->password->setVisible(true);
            $this->keyform->keyfile->setVisible(true);
            $this->keyform->certfile->setVisible(true);

        }
        if($sender->getValue()==2) {
            $this->keyform->serhost->setVisible(true);
            $this->keyform->serport->setVisible(true);
            $this->keyform->usessl->setVisible(true);

        }


    }
    public function onSend($sender) {
        $signtype =  $this->keyform->signtype->getValue()  ;
        $keydata= '';
        $certdata= '';
        $serhost = $this->keyform->serhost->getText() ;
        $serport = $this->keyform->serport->getText() ;
        $usessl  = $this->keyform->usessl->isChecked() ;
        $outher  = $this->keyform->outher->isChecked() ;
        $password = $this->keyform->password->getText() ;
        $keyfile = $this->keyform->keyfile->getFile() ;
        $certfile = $this->keyform->certfile->getFile() ;

        $isjks = strpos($keyfile['name'], '.jks') >0;
        if($signtype==0 || $signtype==1) {
            $keydata =  @file_get_contents($keyfile['tmp_name']);
            $certdata =  !empty($certfile['tmp_name']) ? @file_get_contents($certfile['tmp_name']) : '';

            if(strlen($password)==0  || strlen($keydata)==0) {
                $this->setError('Не вказано необхідні дані') ;
                return;
            }
            if(strlen($certdata)==0 && $isjks == false) {
                $this->setError('Не вказано необхідні дані') ;
                return;
            }

        }
        // if($signtype==0  ) {   //ppolib
        if(true) {   //ppolib

            try {

                if($outher) {
                    $req  = [];
                    $req['key']  = base64_encode($keydata);
                    $req['cert']  =  base64_encode($certdata);
                    $req['pass']  = $password;
                    $req['isjks']  = $isjks;
                    $post = json_encode($req, JSON_UNESCAPED_UNICODE) ;

                    //  $url = "http://local.zstorevue/loadkey.php";
                    $url = "https://key.zippy.com.ua/loadkey.php";
                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                     "Accept: application/json",
                                    "Content-Type: application/json"
                    ));
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                    $result = curl_exec($ch);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE)  ;
                    if (curl_errno($ch) > 0) {
                        //$msg = curl_error($ch);

                    }
                    curl_close($ch) ;



                    $res = json_decode($result) ;

                    if(strlen($res->error) > 0) {
                        $this->setErrorTopPage($res->error) ;

                        return;

                    }
                    $cert=  unserialize(base64_decode($res->cert))  ;
                    $key  = unserialize(base64_decode($res->key)) ;

                } else {

                    if($isjks) {
                        list($key, $cert)= \PPOLib\KeyStore::loadjks($keydata, $password) ;
                    } else {
                        $cert =  \PPOLib\Cert::load($certdata) ;

                        $key =   \PPOLib\KeyStore::load($keydata, $password, $cert) ;

                    }

                    if($key==null) {


                       $this->setErrorTopPage('Invalid  key') ;

                        return;

                    }

                }

                $this->_pos->ppoowner =  $cert->getOwner()   ;
                $this->_pos->ppokeyid =  $cert->getKeyId()   ;
                $this->_pos->ppocert = base64_encode(serialize($cert))  ;
                $this->_pos->ppokey =  base64_encode(serialize($key))  ;


            } catch(\Exception $ee) {
                $msg = $ee->getMessage() ;
                $this->setErrorTopPage($msg) ;
                H::logerror($msg) ;
                return;

            }

        }

        if($signtype==1 || $signtype==2) {
            if(strlen($serhost)==0  || strlen($serport)==0) {
                $this->addAjaxResponse("    $('#progress').text('Не вказано необхідні дані');   $('#send').attr('disabled',null);     ");

                return;
            }

            $req = array();
            $req['serversidekey'] = $signtype==2;

            if($signtype==1) {


                $req['password'] = $password ;
                $req['key'] = base64_encode($keydata);
                $req['cert'] = @base64_encode($certdata);



            }

            $json = json_encode($req)   ;

            $serhost = rtrim($serhost, '/');

            $request = curl_init();
            $url =   $serhost. ":" .$serport . ($isjks ? "/checkjks" : "/check")  ;
            curl_setopt_array($request, [
                CURLOPT_PORT           => $serport,
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYPEER   => $usessl==1,
                CURLOPT_POSTFIELDS     => $json
            ]);

            //самодписаный сертификат
            $fileselfsert = _ROOT . "config/ssl.ser";
            if(file_exists($fileselfsert)) {
                curl_setopt($request, CURLOPT_CAINFO, $fileselfsert) ;
            }


            $ret = curl_exec($request);
            if (curl_errno($request) > 0) {
                $msg = curl_error($request) ;
                $msg = str_replace("'", "\"", $msg) ;

                $this->addAjaxResponse("   $('#progress').text('{$msg}');   $('#send').attr('disabled',null);         ");

                return;
            }

            curl_close($request);
            $ret = json_decode($ret);
            if ($ret->success==false) {
                $msg = $ret->error;
                if(strlen($ret->message)>0) {
                    $msg = $ret->message  ;
                }
                $msg = str_replace("'", "\"", $msg) ;

                $this->addAjaxResponse("    $('#progress').text('{$msg}');   $('#send').attr('disabled',null);          ");

                return;
            }

            if($signtype==1) {   //send  key


                $this->_pos->ppopassword = $password ;
                $this->_pos->ppokey = base64_encode($keydata);
                $this->_pos->ppocert = @base64_encode($certdata);


            }
            $this->_pos->ppoowner =  $ret->owner   ;
            $this->_pos->ppokeyid =  $ret->keyid   ;
            $this->_pos->ppohost =  $serhost  ;
            $this->_pos->ppoport =  $serport   ;
            $this->_pos->ppousessl =  $usessl   ;


        }
        $this->_pos->pposigntype =  $signtype   ;
        $this->_pos->ppoisjks =  $isjks ? 1 : 0   ;
        $this->_pos->save();

        $this->setSuccess("Ключ завантажений") ;

        
        $this->postable->setVisible(true);
        $this->posdetail->setVisible(false);
        $this->keyform->setVisible(false);
        $this->postable->poslist->Reload();
        

    }    
    
}
