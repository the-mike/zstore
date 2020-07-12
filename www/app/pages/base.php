<?php

namespace App\Pages;

use App\Application as App;
use App\Helper;
use App\Session;
use App\System;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;

class Base extends \Zippy\Html\WebPage
{

    public $branch_id = 0;

    public function __construct($params = null) {
        global $_config;


        \Zippy\Html\WebPage::__construct();


        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
            return;
        }


        $this->branch_id = System::getBranch();
        $blist = \App\Entity\Branch::getList(System::getUser()->user_id);
        if (count($blist) == 1) {      //если  одна
            $this->branch_id = array_pop(array_keys($blist));
            System::setBranch($this->branch_id);
        }
        //форма  филиалов       
        $this->add(new \Zippy\Html\Form\Form('nbform'));
        $this->nbform->add(new \Zippy\Html\Form\DropDownChoice('nbbranch', $blist, $this->branch_id))->onChange($this, 'onnbFirm');

        $this->add(new ClickLink('logout', $this, 'LogoutClick'));
        $this->add(new Label('username', $user->username));

        $this->_tvars["docmenu"] = Helper::generateMenu(1);
        $this->_tvars["repmenu"] = Helper::generateMenu(2);
        $this->_tvars["regmenu"] = Helper::generateMenu(3);
        $this->_tvars["refmenu"] = Helper::generateMenu(4);
        $this->_tvars["sermenu"] = Helper::generateMenu(5);

        $this->_tvars["islogined"] = $user->user_id > 0;
        $this->_tvars["isadmin"] = $user->userlogin == 'admin';
        $this->_tvars["isadmins"] = $user->rolename == 'admins';

        $options = System::getOptions('common');

        $this->_tvars["useset"] = $options['useset'] == 1;
        $this->_tvars["usesnumber"] = $options['usesnumber'] == 1;
        $this->_tvars["usescanner"] = $options['usescanner'] == 1;
        $this->_tvars["useimages"] = $options['useimages'] == 1;
        $this->_tvars["usebranch"] = $options['usebranch'] == 1;
        $this->_tvars["useval"] = $options['useval'] == 1;
        $this->_tvars["usefirms"] = $options['usefirms'] == 1;

        if ($this->_tvars["usebranch"] == false) {
            $this->branch_id = 0;
            System::setBranch(0);
        }
        $this->_tvars["smart"] = Helper::generateSmartMenu();

        $this->_tvars["shopmenu"] = $_config['modules']['shop'] == 1;
        $this->_tvars["shop"] = $_config['modules']['shop'] == 1;
        $this->_tvars["ocstore"] = $_config['modules']['ocstore'] == 1;
        $this->_tvars["woocomerce"] = $_config['modules']['woocomerce'] == 1;
        $this->_tvars["note"] = $_config['modules']['note'] == 1;
        $this->_tvars["issue"] = $_config['modules']['issue'] == 1;

        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["shop"] = false;
        }
        if (strpos(System::getUser()->modules, 'note') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["note"] = false;
        }
        if (strpos(System::getUser()->modules, 'issue') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["issue"] = false;
        }
        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["ocstore"] = false;
        }
        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            $this->_tvars["woocomerce"] = false;
        }

        //скрыть  боковое  меню
        $this->_tvars["hidesidebar"] = $user->hidesidebar == 1 ? 'hold-transition   sidebar-collapse' : 'hold-transition sidebar-mini sidebar-collapse';
        //для скрытия блока разметки  в  шаблоне страниц                           
        $this->_tvars["hideblock"] = false;
    }

    public function LogoutClick($sender) {
        setcookie("remember", '', 0);
        System::setUser(new \App\Entity\User());
        $_SESSION['user_id'] = 0;
        $_SESSION['userlogin'] = 'Гость';

        //$page = $this->getOwnerPage();
        //  $page = get_class($page)  ;
        App::Redirect("\\App\\Pages\\UserLogin");;;
        //    App::$app->getresponse()->toBack();
    }

    public function onnbFirm($sender) {
        $branch_id = $sender->getValue();
        System::setBranch($branch_id);

        $page = get_class($this);
        App::Redirect($page);
    }

    //вывод ошибки,  используется   в дочерних страницах
   
    // показывать  вверху окна  и  не  прятать автоматически
    public function setErrorTop($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setErrorMsg($msg);
    }

    public function setSuccessTop($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setSuccessMsg($msg);
    }

    public function setWarnTop($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setWarnMsg($msg);
    }

    public function setInfoTop($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setInfoMsg($msg);
    }
    
    // показывать  во  всплывающем окне
    public function setError($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setErrorMsg($msg,true);
    }

    public function setSuccess($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setSuccessMsg($msg,true);
    }

    public function setWarn($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setWarnMsg($msg,true);
    }

    public function setInfo($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setInfoMsg($msg,true);
    }

    final protected function isError() {
        return (strlen(System::getErrorMsg()) > 0 || strlen(System::getErrorMsg(true)) > 0 ) ;
    }

    public function beforeRender() {
        $user = System::getUser();
        $this->_tvars['notcnt'] = \App\Entity\Notify::isNotify($user->user_id);

        $this->_tvars['alerterror']   = "";
        $this->_tvars['alertwarning'] = "";
        $this->_tvars['alertsuccess'] = "";
        $this->_tvars['alertinfo']    = "";
   

        if (strlen(System::getErrorMsg()) > 0) {
            $this->_tvars['alerterror'] = System::getErrorMsg();
            $this->goAnkor('');
        }
        if (strlen(System::getWarnMsg()) > 0) {
            $this->_tvars['alertwarning'] = System::getWarnMsg();
            $this->goAnkor('');
        }
        if (strlen(System::getSuccesMsg()) > 0) {
            $this->_tvars['alertsuccess'] = System::getSuccesMsg();
            $this->goAnkor('');
        }
        if (strlen(System::getInfoMsg()) > 0) {
            $this->_tvars['alertinfo'] = System::getInfoMsg();
            $this->goAnkor('');
        }
       
    }

    protected function afterRender() {
        $user = System::getUser();
        
        if (strlen(System::getErrorMsg(true)) > 0) {
            $this->addJavaScript("toastr.error('" . System::getErrorMsg(true) . "')        ", true);
        }
        if (strlen(System::getWarnMsg(true)) > 0) {
            $this->addJavaScript("toastr.warning('" . System::getWarnMsg(true) . "')        ", true);
        }
        if (strlen(System::getSuccesMsg(true)) > 0) {
            $this->addJavaScript("toastr.success('" . System::getSuccesMsg(true) . "')        ", true);
        }
        if (strlen(System::getInfoMsg(true)) > 0) {
            $this->addJavaScript("toastr.info('" . System::getInfoMsg(true) . "')        ", true);
        }
        
        $this->setErrorTop('');
        $this->setSuccessTop('');
        $this->setInfoTop('');
        $this->setWarnTop('');
        $this->setError('');
        $this->setSuccess('');
        $this->setInfo('');
        $this->setWarn('');
        

    }

    //Перезагрузить страницу  с  клиента
    //например для  сброса  адресной строки  после  команды удаления
    protected final function resetURL() {
        \App\Application::$app->setReloadPage();
    }

    public function tm($p, $post) {
        return "Привет";
    }


    /**
     * Вставляет  JavaScript  в  конец   выходного  потока
     * @param string  Код  скрипта
     * @param boolean Если  true  - вставка  после  загрузки  документа в  браузер
     */
    public function addJavaScript($js, $docready = false) {
        App::$app->getResponse()->addJavaScript($js, $docready);
    }

    public function goDocView(){
          $this->goAnkor('dankor');
    }

}
