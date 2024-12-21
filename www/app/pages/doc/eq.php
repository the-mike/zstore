<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\AutocompleteTextInput;
use App\Entity\Equipment;
 

/**
 * Страница  документа операции с ОС и НМА
 */
class EQ extends \App\Pages\Base
{
    private $_doc;

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0,$eq_id=0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('optype' ))->onChange($this,'onType');

        $this->docform->add(new DropDownChoice('store' ));
        $this->docform->add(new DropDownChoice('emp' ));
        $this->docform->add(new DropDownChoice('parea' ));
        $this->docform->add(new TextInput('amount'));
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this,'oneqItem');
        $this->docform->add(new AutocompleteTextInput('eq'))->onText($this,'oneqItem');
        $this->docform->add(new AutocompleteTextInput('item'))->onText($this,'oneqItem');
        
        if($eq_id > 0) {
           $eq= Equipment::load($eq_id);
           if($eq != null) {
               $this->docform->eq->setKey($eq_id);
               $this->docform->eq->setText($eq->eq_name);
           }
        }
        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->amount->setText($this->_doc->amount);
            $this->docform->parea->setText($this->_doc->amount);
        } else {
            $this->_doc = Document::create('EQ');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->onType( $this->docform->optype) ;

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }
  
      
    public function onType($sender) {
         $this->docform->store->setVisible(false);
         $this->docform->emp->setVisible(false);
         $this->docform->parea->setVisible(false);
         $this->docform->amount->setVisible(false);
         $this->docform->customer->setVisible(false);
         $this->docform->item->setVisible(false);
         $op=intval($sender->getValue());
         
         if($op==1 || $op==5 || $op==6  || $op==7 ){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly',null);
         }
         if($op==2){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly',null);
            $this->docform->customer->setVisible(true);
         }
         if($op==3){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly','on');
            $this->docform->store->setVisible(true);
            $this->docform->item->setVisible(true);
         }
         if($op==4){
            $this->docform->parea->setVisible(true);
            $this->docform->emp->setVisible(true);
         }
         if($op==8){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly',null);
            $this->docform->customer->setVisible(true);
         }
         if($op==9){
            $this->docform->amount->setVisible(true);
            $this->docform->amount->setAttribute('readonly','on');
            $this->docform->store->setVisible(true);
            $this->docform->item->setVisible(true);
         }
           
    }
    
    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();
        $eq_id = $this->docform->eq->getKey();
        $this->_doc->header_data['eq_id'] = $eq_id;

    
        $this->_doc->amount = H::fa($this->docform->amount->getText());
        $this->_doc->document_number = trim($this->docform->document_number->getText());
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->payment = 0;
        $this->_doc->payed = 0;
      
        if ($this->checkForm() == false) {
            return;
        }

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();

            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Reference\\EqList",$eq_id);
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
  
            $this->setError($ee->getMessage());
            $logger->error( $ee->getMessage()  );


        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

      
   

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}
