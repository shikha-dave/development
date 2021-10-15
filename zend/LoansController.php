<?php
/**
 * Lanet controller class in The UoB Project (Agent Module to manage the loan and other details)
 * @author shikha
 * This class is to manage the cases data back-end
*/ 
namespace Agent\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\Http\Header\SetCookie;
use Zend\I18n\Filter\NumberParse as NumberParse;

/** Custome Use **/
use Admin\Entity as EntityPath;
use Agent\Form\LoansForm;
use Agent\Form\Validator as LoansFormValidator;

class LoansController extends AbstractActionController
{     
    public $casesTable 	= 'cases';
    public $adminType 	= 'AA';
    //Need to check price and remove prefix & post fix  //firstPeriodTermsNumber,secondPeriodInterestRate
    public $priceFieldsArray = array('paymentpretaxConsumer','fixedKurs','flexibleKurs','flexibleInterest','loanAmount','outlayCredit','otherFees','noPaymentsPerYear','handlingFee','adjustedHandlingFee','fixedInterest','durationYears','deedCreationFee','deedFee','dutyRedemption','redemptionRate','referenceRateValue','rateSupplement','loanFinancingNeed','salesRate','saleInterest','salesPrice','currentInterestRate','discount1','discount2','firstPeriodTermsNumber','secondPeriodInterestRate');
    // Remove fields to skip from case history log
    public $fieldsSkipInHistoryArray = array('monthlyPreTax','monthlyPostTax','paymentPreTax','paymentPreTax2','aprPreTax','aprPostTax','exchangeLoss','loanBuyAmount','preInterest','totalOtherFees','dutyAmount','settlementAmount','paymentPercent','paymentPercent2','aprPaymentBasedPosttax','totalRepayment','creditAmount','creditCost','loanAmount','paymentPostTaxYearly','paymentPostTaxAllPeriod','totalInterest','otherFees','clientBalance','deedFee','updateCounter','referenceRateType','tradingCost','currentInterestRate');

    public $isDataupdated = 0;	
    
    public $normalizeChars = array(
    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
    'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
    'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
    'ă'=>'a', 'î'=>'i', 'â'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Î'=>'I', 'Â'=>'A', 'Ș'=>'S', 'Ț'=>'T',
    );
    /**
    * @var Doctrine\ORM\EntityManager
    */
    protected $em;
	
    public function setEntityManager(EntityManager $em) 
    {
        $this->em = $em;
    }
    public function getEntityManager() 
    {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }
        return $this->em;
    }
	
	
    public function getLoanDates($year='', $month='',$day='') 
    {
        if($month !=''){
            $tem = date("$year-$month-$day");
        } else {
            $tem = date('Y-m-d');
        }
        $twentyDaysfutureDate = date('Y-m-d', strtotime('+40 days', strtotime($tem)));
        $twentyDaysfutureDayArr = explode('-',$twentyDaysfutureDate);
        $twentyDaysfutureMonth = $twentyDaysfutureDayArr[1];
        $twentyDaysfutureDay = $twentyDaysfutureDayArr[2];
        if($month ==''){ $curMonth = date("m"); }else{ $curMonth  = $twentyDaysfutureMonth; }
        if($day ==''){	$curDay = date("d"); }else{ $curDay  = $twentyDaysfutureDay; }
        $curYear = date("Y");		
        $loanDateIntValArr =array('0311','0611','0911','1211');
        $loanDateValArr =array('11.03','11.06','11.09','11.12');	
        $loanDateIntPart =$curMonth.$curDay;
        $agentLoanDateArr =array();
        $maxKeyIndex=0;
        foreach($loanDateIntValArr as $payOutKey=>$payOutValue){		
            if($loanDateIntPart<=$payOutValue){
                $keyIndex=$curYear.str_replace('.','',$loanDateValArr[$payOutKey]);	
                $agentLoanDateArr[$keyIndex] = $loanDateValArr[$payOutKey].".".$curYear;			
            }else{
                $keyIndex=($curYear+1).str_replace('.','',$loanDateValArr[$payOutKey]);		
                $agentLoanDateArr[$keyIndex] = $loanDateValArr[$payOutKey].".".($curYear+1);		
            } 
            if($maxKeyIndex<$keyIndex){ $maxKeyIndex = $keyIndex; }
        }
        // code added to show the past date...
        $maxDateVal = $agentLoanDateArr[$maxKeyIndex];
        $maxDateValArr = explode(".", $maxDateVal);
        $minDateVal = $maxDateValArr[0].'-'.$maxDateValArr[1].'.'.($maxDateValArr[2]-1);
        $minDatekey = $maxDateValArr[0].$maxDateValArr[1].($maxDateValArr[2]-1);
        $agentLoanDateArr[$minDatekey]=$minDateVal;
        unset($agentLoanDateArr[$maxKeyIndex]);
        ksort($agentLoanDateArr);		
        foreach($agentLoanDateArr as $agentLoanDateArr){
            $agentLoanDateArr = str_replace('.','-',$agentLoanDateArr);
            $resultAgentLoanDateArr[]=$agentLoanDateArr;	
        }
        return $resultAgentLoanDateArr;
    }
	
    /*
    * Render lanet tab details about loan information 
    * @param N/A
    * @return view variables array
    * @author shikha
    */
    public function loanscaseAction()
    {	
        
        $this->layout('layout/ajaxlayout');
        //Controller pulgin object
        $cntPulginObj = $this->FormattingValuePlugin();
        $em = $this->getEntityManager();
        $caseId  = $this->params('first_param');  
        //Get all case loans
        $loansList = $em->getRepository('Admin\Entity\Loans')->getAllLoanListForLoanTabByCaseId($caseId);   
        //Get Case expected cost to calculate deed fee
        $caseExpectedPropCostArray = $em->getRepository('Admin\Entity\Cases')->getExpectedPropertyCostByCaseId($caseId); 
        $caseCreditPurpose = $caseExpectedPropCostArray[0]['creditPurpose'];
        if(!empty($caseExpectedPropCostArray))
        {
            if((int)$caseExpectedPropCostArray[0]['creditPurpose'] !=2)
            {
                $caseExpectedPropertyCost = $caseExpectedPropCostArray[0]['expectedPropertyCost'];
            }
            else
            {
                $caseExpectedPropertyCost = 0;
            }    
        }
        else
        {
            $caseExpectedPropertyCost = 0;
        }
        $loanId =0;
        if(!empty($loansList)){
            $loanId = $loansList[0]['id'];
        } 
        
        
        //Get Case Update Counter to show up to date indicator in header right corner
        $caseUpdateCounter = $em->getRepository('Admin\Entity\Cases')->getCaseUpdateCounter($caseId);  
        return new ViewModel(array( 
            'loansList' => $loansList,
            'caseExpectedPropertyCost' => $caseExpectedPropertyCost,
            'caseUpdateCounter'=> $caseUpdateCounter,
            'caseId' => $caseId,
            'loanId' => $loanId,
            'cntPulginObj' => $cntPulginObj,
            'caseCreditPurpose'=>$caseCreditPurpose,
           
        ));	
    }	
	
    
     /*
    * Funtion to delete a property for a case
    * @param propertyid
    * @return json array
    * @author shikha
    */	
    public function deleteLoanAction()
    {
        $this->layout('layout/ajaxlayout');
        $request    = $this->getRequest(); 
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        //Get agent details from session
        $agent_session 	= new Container('Uobmember');		
        $username = $agent_session->userName;
        $userId = $agent_session->userId;
        if(!isset($userId))
        {
            $result = array('status' => 'error', 'message' => 'Agent session destroy');
        } 
        if($request->isXmlHttpRequest() && isset($userId))
        { 
            $em = $this->getEntityManager();
            $postData   = $request->getPost();
            $caseId     = $postData['caseId'];	
            $loanIdArray = $postData['loanIdArray'];
            
            $formUpdateCounter  = $postData['caseUpdateCounter'];        
            $isCaseUpToDate     = $em->getRepository('Admin\Entity\Cases')->checkIsCaseUpToDate($caseId,$formUpdateCounter);
            if(!$isCaseUpToDate)
            {
                $result =  array('response'=>"multiuserupdate", 'message'=>"stdDisplayMsg.LOAN_MULTIUSER_UPDATE_RESTRICTION_MSG");
            }
            else 
            {
                 //Get total property w.r.t case id
                $totalDbloans = $em->getRepository('Admin\Entity\Loans')->countLoansByCaseId($caseId);
                $totalRequestLoan = count($loanIdArray);
                if((int)$totalDbloans !== (int)$totalRequestLoan)
                {	
                    $deleteDocumentIdArr = array();	
                    $cancelDocumentSignOrderIdArr = array();;	
                    //Get deleted document ids
                    $deletedDocumentIdArray =  $em->getRepository('Admin\Entity\Documents')->getLoansDeletedDocumentIdWrtLonsId($caseId,$loanIdArray);
                    if(!empty($deletedDocumentIdArray))
                    {
                        foreach($deletedDocumentIdArray as $deletedDocIdArr){
                            $deleteDocumentIdArr[] = $deletedDocIdArr['id'];
                            if($deletedDocIdArr['signatureOrderId']!='')
                                $cancelDocumentSignOrderIdArr[]=$deletedDocIdArr['signatureOrderId'];
                        }
                        $deleteDocumentIdArr = array_unique($deleteDocumentIdArr);
                        $cancelDocumentSignOrderIdArr = array_unique($cancelDocumentSignOrderIdArr);
                    }    
                    //Delete loans
                    $em->getRepository('Admin\Entity\Loans')->deleteLoansByLoanId($caseId,$loanIdArray);                    
                    //Maintain in History                    
                    $updateremail = $agent_session->userName;
                    $updatedbyid = $agent_session->userId;                   
                    //Delete Loans all document
                    $em->getRepository('Admin\Entity\Documents')->deleteDocumentByDocumentId($caseId,$deleteDocumentIdArr);
                    
                    //Delete Favorite Documents
                    $favoriteStatus = 0;
                    $em->getRepository('Admin\Entity\Documents')->deleteFavoriteDocumentByDocumentId($deleteDocumentIdArr,$favoriteStatus);
                    
                    //end                    
                    //Maintain in History   
                    $addedDate = date("Y-m-d H:i:s");//Used in casehistory table to fixed deleted bundel issues
                    $action="Deleted Document";
                    foreach($deleteDocumentIdArr as $documentIdInLoop)
                    {   
                        $documentData = $em->getRepository('Admin\Entity\Documents')->getDocumentNameByDocumentId($documentIdInLoop);	
                        $documentName = $documentData[0]['documentdisplayname'];
                        $documentArr  = array('documentName'=>$documentName,'oldDocumentName'=>'','documentId'=>$documentIdInLoop);
                        $em->getRepository('Admin\Entity\Casehistory')->logCasehistoryDocumentData($documentArr,$caseId,$updatedbyid,$updateremail,$action,$addedDate);
                    } 
                    
                    $action="Deleted Loans";                      
                    foreach($loanIdArray as $loanIdInLoop)
                    {   
                        $loanData = $em->getRepository('Admin\Entity\Loans')->getLoanDetailsByloanId($loanIdInLoop);	
                        $loanData = $loanData[0]['loanId'];
                        $em->getRepository('Admin\Entity\Casehistory')->logCasehistoryLoansAddDelete($loanData,$loanIdInLoop,$caseId,$updatedbyid,$updateremail,$action,$addedDate);
                    }
                    //Update case modify date
                    $em->getRepository('Admin\Entity\Cases')->updateCaseModifiedDateCaseID($caseId);	
                    
                    //Get tottal finanacing need ammount for all loans w.r.t case
                    $sumOfAllLoansFinancingNeedAmount = $em->getRepository('Admin\Entity\Loans')->getTotlalFinancingNeedByCaseId($caseId);
                    $sumOfAllLoansFinancingNeedAmount = number_format($sumOfAllLoansFinancingNeedAmount,0,",",".");
                    /*Delete the rows in case_loan_properties by loan id array*/
                    $em->getRepository('Admin\Entity\CaseLoanProperties')->deleteCLPByLoanId($loanIdArray);
                    
                    $result = array( 'response'=>"success", 'message'=>"stdDisplayMsg.LOAN_DELETE_MSG",'deleteDocumentIdArr'=>$deleteDocumentIdArr,'cancelDocumentSignOrderIdArr'=>$cancelDocumentSignOrderIdArr,'sum_of_all_loans_financing_need_amount'=>$sumOfAllLoansFinancingNeedAmount);	
                    //Update case update counter 
                    $em->getRepository('Admin\Entity\Cases')->updateCaseCounterByCaseId($caseId,$formUpdateCounter);
                    
                    foreach($loanIdArray as $loanId)
                    {  
                    $loanEconomicData = $em->getRepository('Admin\Entity\CaseEconomic')->getAllLoanEconomicDataByLoanId($loanId);
                    if(count($loanEconomicData)>=1){
                        foreach($loanEconomicData as $loanEconomicDataObject){   
                            $invoiceType	= $loanEconomicDataObject['economicEntityType'];
                            $invoiceStatus	= $loanEconomicDataObject['economicStatus'];
                            $uniqueId           = $loanEconomicDataObject['uniqueId'];
                            $economicId         = $loanEconomicDataObject['economicId'];   
                            if($invoiceStatus == 'DRAFT'){
                                if($this->deleteLoansInvoiceFromEconomic($economicId)){
                                    $em->getRepository('Admin\Entity\CaseEconomic')->deleteLoanEconomicData($uniqueId,$economicId);   
                                    $agent_session = new Container('Uobmember');
                                    $updateremail = $agent_session->userName;
                                    $updatedbyid = $agent_session->userId;
                                    $action= "Deleted Invoice"; 
                                    $invoiceArr  = array('invoiceName'=>'Invoice','oldInvoiceName'=>'','invoiceId'=>$loanId,'displayTitle'=> $invoiceType);
                                    $em->getRepository('Admin\Entity\Casehistory')->logCasehistoryInvoiceData($invoiceArr,$caseId,$updatedbyid,$updateremail,$action);
                                    //is agent commission status required to update in loan table adde by javed on 3-Apr-17
                                    $commiPaidOut = $em->getRepository('Admin\Entity\Loans')->isLoanCommissionStatusPaidOutByLoanId($loanId);
                                    if(!$commiPaidOut){
                                        if(trim(strtoupper($invoiceType))== 'KREDITNOTA'){
                                            $isFaktDocExist = $em->getRepository('Admin\Entity\CaseEconomic')->isFakturaDocumentExist($caseId,$loanId);
                                            if(!$isFaktDocExist){
                                                //empty status
                                                $earnedStatus = NULL;
                                                $em->getRepository('Admin\Entity\Loans')->updateAgentCommissionStatusByLoandId($loanId,$earnedStatus);
                                            }
                                        }
                                        elseif (trim(strtoupper($invoiceType))== 'FAKTURA'){
                                            $isKreditNotaDocExist = $em->getRepository('Admin\Entity\CaseEconomic')->isKreditNotaDocumentExist($caseId,$loanId);
                                            if($isKreditNotaDocExist){
                                                //Earned status->1
                                                $earnedStatus = 1;
                                                $em->getRepository('Admin\Entity\Loans')->updateAgentCommissionStatusByLoandId($loanId,$earnedStatus);
                                            }else{
                                                //empty status
                                                $earnedStatus = NULL;
                                                $em->getRepository('Admin\Entity\Loans')->updateAgentCommissionStatusByLoandId($loanId,$earnedStatus);
                                            }
                                        }
                                    }
                                }
                                else{   
                                    $result =  array('response'=>"fail", 'message'=>"stdDisplayMsg.ECONOMIC_ERROR_IN_DELETE_DATA"); //Error in delete from economic
                                }    
                            }
                            elseif($invoiceStatus == 'BOOKED'){
                                if($this->deleteLoansBookedInvoiceFromEconomic($economicId,$uniqueId)){
                                    $em->getRepository('Admin\Entity\CaseEconomic')->deleteLoanEconomicData($uniqueId,$economicId);   
                                    $agent_session = new Container('Uobmember');
                                    $updateremail = $agent_session->userName;
                                    $updatedbyid = $agent_session->userId;
                                    $action = "Deleted Invoice"; 
                                    $invoiceArr  = array('invoiceName'=>'Invoice','oldInvoiceName'=>'','invoiceId'=>$loanId,'displayTitle'=> $invoiceType);
                                    $em->getRepository('Admin\Entity\Casehistory')->logCasehistoryInvoiceData($invoiceArr,$caseId,$updatedbyid,$updateremail,$action);

                                    $result =  array('response'=>"success",  'message'=>"stdDisplayMsg.ECONOMIC_DELETE_MSG");//Invoice data deleted
                                    //is agent commission status required to update in loan table adde by javed on 3-Apr-17
                                    $commiPaidOut = $em->getRepository('Admin\Entity\Loans')->isLoanCommissionStatusPaidOutByLoanId($loanId);
                                    if(!$commiPaidOut){
                                        if(trim(strtoupper($invoiceType))== 'KREDITNOTA'){
                                            $isFaktDocExist = $em->getRepository('Admin\Entity\CaseEconomic')->isFakturaDocumentExist($caseId,$loanId);
                                            if(!$isFaktDocExist){
                                                //empty status
                                                $earnedStatus=NULL;
                                                $em->getRepository('Admin\Entity\Loans')->updateAgentCommissionStatusByLoandId($loanId,$earnedStatus);
                                            }
                                        }
                                        elseif (trim(strtoupper($invoiceType))== 'FAKTURA'){
                                            $isKreditNotaDocExist = $em->getRepository('Admin\Entity\CaseEconomic')->isKreditNotaDocumentExist($caseId,$loanId);
                                            if($isKreditNotaDocExist){
                                                //Earned status->1
                                                $earnedStatus=1;
                                                $em->getRepository('Admin\Entity\Loans')->updateAgentCommissionStatusByLoandId($loanId,$earnedStatus);
                                            }
                                            else{
                                                //empty status
                                                $earnedStatus = NULL;
                                                $em->getRepository('Admin\Entity\Loans')->updateAgentCommissionStatusByLoandId($loanId,$earnedStatus);
                                            }
                                        }
                                    }
                                }
                                else{
                                   
                                }
                            }                            
                        }                        
                    }
                    $loanInvoicesData = $em->getRepository('Admin\Entity\ClientInvoices')->getClientInvoicesByloanId($loanId);
                    if(count($loanInvoicesData)>=1){
                        foreach($loanInvoicesData as $loanInvoicesDataObject){  
                            $invoiceStatus	 = $loanInvoicesDataObject['economicStatus'];	
                            $uniqueId            = $loanInvoicesDataObject['uniqueId'];
                            $economicId          = $loanInvoicesDataObject['economicId'];   
                            // code to delete the invoice from E-conomic.
                            if($invoiceStatus == 'DRAFT'){
                                if($this->deleteLoansInvoiceFromEconomic($economicId)){
                                    $em->getRepository('Admin\Entity\ClientInvoices')->deleteClientLoanEconomicData($uniqueId,$economicId);    
                                }else{   
                                   
                                }    
                            }elseif($invoiceStatus == 'BOOKED'){
                                if($this->deleteLoansBookedInvoiceFromEconomic($economicId,$uniqueId)){
                                   $em->getRepository('Admin\Entity\ClientInvoices')->deleteClientLoanEconomicData($uniqueId,$economicId);   
                                }
                            }
                       }                        
                    }
                }                
                }
                else
                {
                    $result =  array( 'response'=>"fail", 'message'=>"Du kan ikke slette alle lån!");//stdDisplayMsg.LOAN_CANNOT_DELETE_ALL_LOAN_MSG	
                }                
            }
        }
        echo json_encode($result);
        exit();
    }
    
    
     /*
    * Funtion to delete a property for a case
    * @param propertyid
    * @return json array
    * @author shikha
    */	
    public function addNewLoanAction()
    {
        $this->layout('layout/ajaxlayout');
        $request    = $this->getRequest(); 
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        //Get agent details from session
        $agent_session 	= new Container('Uobmember');		
        $username = $agent_session->userName;
        $userId = $agent_session->userId;
        if(!isset($userId))
        {
            $result = array('status' => 'error', 'message' => 'Agent session destroy');
        } 
        if($request->isXmlHttpRequest() && isset($userId))
        { 
            $em = $this->getEntityManager();
            $postData   = $request->getPost();
            $caseId     = $postData['caseId'];           
            $formUpdateCounter  = $postData['caseUpdateCounter'];        
            $isCaseUpToDate     = $em->getRepository('Admin\Entity\Cases')->checkIsCaseUpToDate($caseId,$formUpdateCounter);
            if(!$isCaseUpToDate)
            {
                $result =  array('response'=>"multiuserupdate", 'message'=>"stdDisplayMsg.LOAN_MULTIUSER_UPDATE_RESTRICTION_MSG");
            }
            else 
            {    
                //Maintain in History                    
                $updateremail = $agent_session->userName;
                $updatedbyid = $agent_session->userId;
                $action ="Added New Loan";              
                //Get Admin setting data for loan calculation
                $adminVariable = $em->getRepository('Admin\Entity\CalcMeta')->getAdminVariableSetting();
                $loanData = $em->getRepository('Admin\Entity\Loans')->addNewLoanFromAgent($caseId,$adminVariable);
                $em->getRepository('Admin\Entity\Casehistory')->logCasehistoryLoansAddDelete($loanData['loandIdStr'],$loanData['id'],$caseId,$updatedbyid,$updateremail,$action);
                //Update case modify date
                $em->getRepository('Admin\Entity\Cases')->updateCaseModifiedDateCaseID($caseId);	
                
                //Get tottal finanacing need ammount for all loans w.r.t case
                $sumOfAllLoansFinancingNeedAmount = $em->getRepository('Admin\Entity\Loans')->getTotlalFinancingNeedByCaseId($caseId);
                $sumOfAllLoansFinancingNeedAmount = number_format($sumOfAllLoansFinancingNeedAmount,0,",",".");
                
                $caseFirstLoanPropertyData = $em->getRepository('Admin\Entity\CaseLoanProperties')->getPrimaryLoanPropertyByCaseId($caseId);
                if(!empty($caseFirstLoanPropertyData)){
                    $newLoanAssignedProperty = $em->getRepository('Admin\Entity\CaseLoanProperties')->addPropertyToNewLoan($caseFirstLoanPropertyData['propertyId'],$caseId,$loanData['id']);
                }
                
                $result = array('response'=>"success", 'message'=>"stdDisplayMsg.LOAN_ADDED_SUCCESSFULLY_MSG",'loanId'=>$loanData['id'],'sum_of_all_loans_financing_need_amount'=>$sumOfAllLoansFinancingNeedAmount);	
                //Update case update counter 
                $em->getRepository('Admin\Entity\Cases')->updateCaseCounterByCaseId($caseId,$formUpdateCounter);
            }                           
        }
        echo json_encode($result);
        exit();
    }
    
    /*
    * Get or update latent tab data 
    * @param N/A
    * @return JSON array
    * @author shikha
    */
    public function updateloansdataAction()
    {  
        $this->layout('layout/ajaxlayout');		
        $admin_session = new Container('Uobmember');
        $username = $admin_session->userName;
        if ($username =='') { 		
            return $this->redirect()->toRoute('admin');
        }
        $userId = $admin_session->userId;		
        //Controller pulgin object
        $cntPulginObj = $this->FormattingValuePlugin();
        $em = $this->getEntityManager();
        $request  = $this->getRequest(); 	
        $postData = $request->getPost();	
     
        $caseId  = $postData['caseId'];
        $loanId  = $postData['loanId'];
        //Hidden fields counter
        $formUpdateCounter  = $postData['updateCounter'];
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.','loanId'=>$loanId);
        //Get agent details from session   
        if($request->isXmlHttpRequest() && isset($userId))
        {  
            //check if data need to update.	
            $oldValueDataArray = array(); //Old data array
            $updateValueDataArray 	= array(); // New value data       
            if($request->isPost()) 
            { 
                if(isset($postData['payoutDate']) && $postData['payoutDate']!='')
                {
                    $payoutDate = $postData['payoutDate'];
                    $payoutDateArr = explode('.',$payoutDate);	
                    $agentLoanDateArr  = $this->getLoanDates($payoutDateArr[2],$payoutDateArr[1],$payoutDateArr[0]);
                }
                else
                {
                    $agentLoanDateArr  = array();
                    $postData['loanDate'] ='';
                }
                //Get Investor list	
                $investorList = $em->getRepository('Admin\Entity\Investors')->getInvestorList();
                
                // code for get the ownership change data.
                $ownershipchangeData = $em->getRepository('Admin\Entity\Ownershipchange')->getOwnershipchangeData();
                
                $caseLoanDetail = array();        
                if(!empty($loanId)){
                    $caseLoanDetail = $em->getRepository('Admin\Entity\Loans')->getLoanDetailsByloanId($loanId);          
                } 
        
                $showCaseId =10000+$caseId;                
                if($caseLoanDetail[0]['loanId'] !=='UOB#'.$showCaseId.'-001'){
                    $loanTypeData = $em->getRepository('Admin\Entity\LoanType')->getloanTypeListData();   // loans other them main loan           
                }else{
                    $loanTypeData = $em->getRepository('Admin\Entity\LoanType')->getMainLoanTypeListData(); 
                }
                $documentexists='';
                $isDocExist = $em->getRepository('Admin\Entity\ClientInvoices')->getClientInvoicesByloanId($loanId);
                    if(!empty($isDocExist)) {
                        $documentexists='exists'; 
                    }
                
                //code for get Repayment profile Data.
                $repaymentProfileMasterData = $em->getRepository('Admin\Entity\RepaymentProfileMaster')->getRepaymentProfileMasterData();
                $loansFormObj = new LoansForm(null,array(),array('investorId'=>$investorList,'agentLoanDateArr'=>$agentLoanDateArr,'ownershipchangeData'=>$ownershipchangeData,'loanTypeData'=>$loanTypeData,'repaymentProfileMasterData'=>$repaymentProfileMasterData,'documentexists'=>$documentexists));
                //$loansFormObj = new LanetForm();
                $formValidator = new LoansFormValidator\LanetFormValidator();				
                $loansFormObj->setInputFilter($formValidator->getInputFilter());
                //echo $postData['paymentPreTax'];die;
                if(isset($postData['loanType']) && $postData['loanType']=='Forbruger'){
                    $fieldString = array('loanFinancingNeed','durationYears','loanType','investorId',
                        'paymentPreTax','PrePaymentTaxConsumer');
                            $fieldString[] ='fixedInterest';
                            $fieldString[] ='paymentpretaxConsumer';
                }else{                    
                        $fieldString = array('loanIssue','PrePaymentTaxConsumer','acquisitionTerms',
                            'ownershipChange','handlingFee','adjustedHandlingFee','loanAmount','noPaymentsPerYear',
                            'outlayCredit','rateType','updateCounter','redemptionRate',
                            'durationYears','redemptionTerms','monthlyPreTax','monthlyPostTax',
                            'paymentPreTax','paymentPreTax2','aprPreTax','aprPostTax','exchangeLoss','loanBuyAmount',
                            'preInterest','dutyAmount','settlementAmount','totalOtherFees','paymentPercent','paymentPercent2','aprPaymentBasedPosttax','totalRepayment','creditAmount','creditCost',
                            'paymentPostTaxYearly','paymentPostTaxAllPeriod','totalInterest',
                            'deedCreationFee','deedFee','dutyRedemption',
                            'addOutlaycreditToLoan','addDeedcostsToLoan','addDeedCreationFeeToLoan',
                            'tradingCost','clientBalance','saleInterest',
                            'loanFinancingNeed','repaymentProfile','discount1','discount2','discount1Text',
                            'discount2Text');
                        $fieldString[] ='investorId';
                        $fieldString[] ='currentInterestRate';
                        $fieldString[] ='saleDate';
                        $fieldString[] ='salesInterestFrom';
                        //Check is economic document exist w.r.t case id if exist then remove from updation and view file as read only
                        $isIconomicDocExist = $em->getRepository('Admin\Entity\CaseEconomic')->isNonDeletedEconomicDocumentExist($caseId,$loanId);
                        if(!$isIconomicDocExist){
                            $fieldString[] ='salesRate';
                        }
                        $fieldString[] ='salesPrice';
                        $fieldString[] ='loanDesc';
                        $fieldString[] ='additionalInfo';
                        $fieldString[] ='expirationRedemptionRate';
                        $fieldString[] ='loanType';

                        if(isset($postData['payoutDate']) && $postData['payoutDate']!='')
                        {
                            $fieldString[] ='payoutDate';
                        }	
                        if(strtolower($postData['rateType']) == 'variabel')
                        {				
                            $fieldString[] ='referenceRateValue';
                            $fieldString[] ='rateSupplement';
                            $fieldString[] ='referenceRateType';
                            $fieldString[] ='flexibleKurs';
                            $fieldString[] ='flexibleInterest';
                        }
                        else if(strtolower($postData['rateType']) == 'fast')
                        {  
                            $fieldString[] ='fixedKurs';
                            $fieldString[] ='fixedInterest';
                        }
                        if(isset($postData['firstPeriodTermsNumber']) && $postData['firstPeriodTermsNumber']!=''){
                            $fieldString[] ='firstPeriodTermsNumber';
                        }
                        
                        if(isset($postData['secondPeriodInterestRate']) && $postData['secondPeriodInterestRate']!=''){
                            $fieldString[] ='secondPeriodInterestRate';
                        }

                        $isTinglisingCase = $em->getRepository('Admin\Entity\Cases')->isTinglisingCaseExist($caseId);
                        if($isTinglisingCase == 1 || $isTinglisingCase == 3 || $isTinglisingCase == 5) {
                            $fieldString[] ='createDeed';
                            $fieldString[] ='weGuarantee';
                            $fieldString[] ='deedRectification';
                        }
                        $postData['PrePaymentTaxConsumer']=0;
                }
                        
                
                $loansFormObj->setValidationGroup($fieldString);
                $loansFormObj->setData($postData);
                 
                if($loansFormObj->isValid()) 
                { 
                    //code for get current case data.
                    $caseDetail = $em->getRepository('Admin\Entity\Loans')->getLoanDetailsByloanId($loanId);
                    
                    $fixedKurs     = $caseDetail[0]['fixedKurs'];
                    $flexibleKurs  = $caseDetail[0]['flexibleKurs'];
                    $caseDataDependOnLoans = $em->getRepository('Admin\Entity\Loans')->getCaseDataDependOnLoans($caseId);
                    
                    //merge case data in array
                    if(!empty($caseDataDependOnLoans))
                    {
                        foreach($caseDataDependOnLoans as $ckey=>$cvalue)
                        {
                            $caseDetail[0][$ckey] = $cvalue;
                        }    
                    } 

                    if(isset($caseDetail[0]['loanDate']))
                    {
                        $caseDetail[0]['loanDate'] = $caseDetail[0]['loanDate']->format('d.m.Y'); 
                        $caseDetail[0]['loanDate'] = $cntPulginObj->dateFormater($caseDetail[0]['loanDate'],'DD.MM.YYYY');
                    }	 
                    if(isset($caseDetail[0]['payoutDate']))
                    {
                        $caseDetail[0]['payoutDate'] = $caseDetail[0]['payoutDate']->format('d.m.Y');
                        $caseDetail[0]['payoutDate'] = $cntPulginObj->dateFormater($caseDetail[0]['payoutDate'],'DD.MM.YYYY');
                    }                    
                    if(isset($caseDetail[0]['saleDate']))
                    {
                        $caseDetail[0]['saleDate'] = $caseDetail[0]['saleDate']->format('d.m.Y');
                        $caseDetail[0]['saleDate'] = $cntPulginObj->dateFormater($caseDetail[0]['saleDate'],'DD.MM.YYYY');
                    }                    
                    if(isset($caseDetail[0]['salesInterestFrom']))
                    {
                        $caseDetail[0]['salesInterestFrom'] = $caseDetail[0]['salesInterestFrom']->format('d.m.Y');
                        $caseDetail[0]['salesInterestFrom'] = $cntPulginObj->dateFormater($caseDetail[0]['salesInterestFrom'],'DD.MM.YYYY');
                    }
                    //Latest Update Counter
                    $latestUpdateCounter = $caseDetail[0]['updateCounter'];
                    if(!isset($latestUpdateCounter))
                        $latestUpdateCounter = 0;

                    //check updated permission
                    $postDataArray 	 = $loansFormObj->getData();
                    $postDataArray['loanDate'] =$postData['loanDate'];	
                    $dbDataArray	 = $caseDetail[0];
                    $commonKeyArray  = array_intersect_key($dbDataArray,$postDataArray);
                    //Its array is use to treated blank and 0 as equal to avoid multiple entry revison history
                    $zeroBlankIssuesElement = array('addDeedCreationFeeToLoan','addDeedcostsToLoan',
                        'addOutlaycreditToLoan','weGuarantee','otherFees','loanIssue',
                        'createDeed','acquisitionTerms','experience','priority','deedRectification',
                        'investorId','saleInterest');		
                    //Complex flexible interest rate 
                    if(strtolower($postData['rateType']) == 'variabel')
                    {	
                        $filterNew = new \Zend\I18n\Filter\NumberParse("de_DE");
                        $referenceRateValue =  $filterNew->filter($postData['referenceRateValue']);
                        $rateSupplement =  $filterNew->filter($postData['rateSupplement']);					
                        $postData['flexibleInterestNew'] = $referenceRateValue+$rateSupplement;//Not in use
                        $postData['flexibleInterest'] = $referenceRateValue+$rateSupplement;
                        $postData['currentInterestRate'] = $referenceRateValue+$rateSupplement;
                    }
                    else {
                        $postData['currentInterestRate'] = $postData['fixedInterest'];
                    }
                    //echo 'dis :- '.$postData['discount1Text']."<br>";
                    foreach($commonKeyArray as $fieldKey => $fieldValue)
                    {
                        //Check only for price which one set in priceFieldsArray array fields
                        if(in_array($fieldKey,$this->priceFieldsArray)){
                            $filter = new \Zend\I18n\Filter\NumberParse("de_DE");
                            $postData[$fieldKey] =  $filter->filter($postData[$fieldKey]);
                        }
                        //Its array is use to treated blank and 0 as equal so no need to update in db and fix issue to store multiple entry revison history
                        if (in_array($fieldKey, $zeroBlankIssuesElement))
                        {
                            if(($postData[$fieldKey] == 0 && trim($fieldValue)== '') || ($postData[$fieldKey] == 'NULL' && trim($fieldValue)==0))
                            {					
                                continue; 
                            }
                        }
                   
                        if(((strtolower(trim($postData[$fieldKey])) != strtolower(trim($fieldValue))) && ($postData[$fieldKey]!=='')) ||(((strtolower(trim($fieldKey))) =='paymentpretaxconsumer') && ( $postData[$fieldKey] !=$fieldValue) ) ||(((strtolower(trim($fieldKey))) =='additionalinfo') && ( $postData[$fieldKey] !=$fieldValue) ) || (((strtolower(trim($fieldKey))) =='discount1') && ( $postData[$fieldKey] !=$fieldValue) ) ||(((strtolower(trim($fieldKey))) =='discount2') && ( $postData[$fieldKey] !=$fieldValue) ) ||(((trim($fieldKey)) =='discount1Text') && ( $postData[$fieldKey] !=$fieldValue) ) ||(((trim($fieldKey)) =='discount2Text') && ( $postData[$fieldKey] !=$fieldValue) )){					
                            $this->isDataupdated = 1;	  	
                            if($fieldKey=="expirationRedemptionRate"){
                                $updateValueDataArray[$fieldKey] = str_replace(",",".",$postData[$fieldKey]);
                            }
                            else if($fieldKey=="secondPeriodInterestRate" ){
                                $secondPeriodInterestRate = str_replace(",",".",$postData[$fieldKey]);
                                if($secondPeriodInterestRate == 'null' && trim($fieldValue)== NULL || $secondPeriodInterestRate == 'null' && trim($fieldValue)== 0){
                                }else if($secondPeriodInterestRate != $fieldValue ){
                                    $updateValueDataArray[$fieldKey] = $secondPeriodInterestRate;
                                }
                            }
                            else if(($fieldKey=="firstPeriodTermsNumber") && ($postData[$fieldKey] == 'null' && trim($fieldValue)== NULL || $postData[$fieldKey] == 'null' && trim($fieldValue)== 0)){
                            }
                            else{
                                $updateValueDataArray[$fieldKey] = $postData[$fieldKey];
                            }
                            $oldValueDataArray[$fieldKey] = $fieldValue; 
                        }else if(($fieldKey=='salesInterestFrom') || ($fieldKey=='saleDate')) {
                           
                            if(strtolower(trim($postData[$fieldKey])) != strtolower(trim($fieldValue))) {
                                $this->isDataupdated = 1;							
                                $updateValueDataArray[$fieldKey] = $postData[$fieldKey];
                                $oldValueDataArray[$fieldKey] = $fieldValue;  
                            }
                        }
                        
                    }
                    //All other fields update		
                    if($this->isDataupdated == 1)
                    {
                        //Check fields permission				
                        if(!empty($updateValueDataArray))
                        {
                            //Get all Permission fields
                            $agent_session = new Container('Uobmember');
                            $userTypeKey = $agent_session->userTypeKey;
                            if($userTypeKey != $this->adminType)
                            {	
                                //Get Group stage
                                
                                $caseStageArr = $em->getRepository('Admin\Entity\Cases')->getCaseStageData($caseId);
                                $stage = ucfirst($caseStageArr[0]['stage']);
                                $groupStage = $em->getRepository('Admin\Entity\Casestages')->getGroupStage($stage);	
                                $allPermissionFields = $em->getRepository('Admin\Entity\Permissions')->getPermissionFields($userTypeKey,$this->casesTable,$groupStage);
                                $updateFieldsError  = array();
                                foreach($updateValueDataArray as $fKey => $fValueArr)
                                {
                                    if(isset($allPermissionFields[$fKey]) && $allPermissionFields[$fKey]==0)
                                        $updateFieldsError[] = $fKey;
                                }						
                                if(!empty($updateFieldsError))
                                {
                                    $result =   array( 'response'=>"failed", 'message'=>"You don't have permission to update following fields:".implode(",",$updateFieldsError)." Please contact administrator",'loanId'=>$loanId);
                                    echo json_encode($result);
                                    exit(); 
                                }
                            }
                        }					
                        //Check update permission fields in case of multiple user update
                        $returnResponse = $this->checkFieldsUpdatePermission($updateValueDataArray,$caseId,$formUpdateCounter,$latestUpdateCounter);
                        if($returnResponse['response'] == 'success')
                        {
                            // code to add record in case history table
                            $newUpdateCounter = $latestUpdateCounter+1;	
                            //Added in case history
                            $filterUpdateValueDataArray = array();
                            $filterOldValueDataArray = array();
                            //Convert key with value & value with key
                            $this->fieldsSkipInHistoryArray = array_flip($this->fieldsSkipInHistoryArray);
                            $filterUpdateValueDataArray = array_diff_key($updateValueDataArray,$this->fieldsSkipInHistoryArray);						
                            $filterOldValueDataArray = array_diff_key($oldValueDataArray,$this->fieldsSkipInHistoryArray);

                            //Set select option label to update in case history						
                            foreach($filterUpdateValueDataArray as $newKey=>$newValue)
                            {   
                                if($newKey === 'loanType'){
                                    if($newValue == ''){$newValue = 0;}
                                    $filterUpdateValueDataArray[$newKey] =$newValue;
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue == ''){$oldValue = 0;}
                                    $filterOldValueDataArray[$newKey] = $oldValue;                                    
                                }
                                else if($newKey === 'ownershipChange'){
                                    $newValue = $em->getRepository('Admin\Entity\Ownershipchange')->getOwnershipchangeTitleById($newValue);
                                    $filterUpdateValueDataArray[$newKey] = $newValue;
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue!==0)
                                        $oldValue = $em->getRepository('Admin\Entity\Ownershipchange')->getOwnershipchangeTitleById($oldValue);
                                    $filterOldValueDataArray[$newKey] = $oldValue;
                                }
                                else if($newKey === 'repaymentProfile'){
                                    $newValue = $em->getRepository('Admin\Entity\RepaymentProfileMaster')->getRepaymentProfileMasterTitleById($newValue);
                                    $filterUpdateValueDataArray[$newKey] = $newValue;
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue!==0)
                                        $oldValue = $em->getRepository('Admin\Entity\RepaymentProfileMaster')->getRepaymentProfileMasterTitleById($oldValue);
                                    $filterOldValueDataArray[$newKey] = $oldValue;
                                }
                                else if($newKey === 'loanIssue'){
                                    $loanIssueArr = array('0'=>'Cirkulerende','1'=>'Nyudstedelse');
                                    $newValue = $filterUpdateValueDataArray[$newKey];
                                    if($newValue == ''){$newValue = 0;}
                                    $filterUpdateValueDataArray[$newKey] = $loanIssueArr[$newValue];
                                    //Update old values// array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue == ''){$oldValue = 0;}
                                    $filterOldValueDataArray[$newKey] = $loanIssueArr[$oldValue];
                                }
                                else if($newKey === 'weGuarantee'){
                                    $weGuaranteeArr = array('0'=>'Nej','1'=>'Ja');
                                    $newValue = $filterUpdateValueDataArray[$newKey];
                                    if($newValue == ''){$newValue = 0;}
                                    $filterUpdateValueDataArray[$newKey] = $weGuaranteeArr[$newValue];
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue == ''){$oldValue = 0;}
                                    $filterOldValueDataArray[$newKey] = $weGuaranteeArr[$oldValue];
                                }
                                else if($newKey === 'createDeed'){
                                    $createDeedArr = array('0'=>'Nej','1'=>'Ja');
                                    $newValue = $filterUpdateValueDataArray[$newKey];
                                    if($newValue == ''){$newValue = 0;}
                                    $filterUpdateValueDataArray[$newKey] = $createDeedArr[$newValue];
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue == ''){$oldValue = 0;}
                                    $filterOldValueDataArray[$newKey] = $createDeedArr[$oldValue];
                                } else if($newKey === 'deedRectification'){
                                    $deedRectificationArr = array('0'=>'Nej','1'=>'Ja');
                                    $newValue = $filterUpdateValueDataArray[$newKey];
                                    if($newValue == ''){$newValue = 0;}
                                    $filterUpdateValueDataArray[$newKey] = $deedRectificationArr[$newValue];
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue == ''){$oldValue = 0;}
                                    $filterOldValueDataArray[$newKey] = $deedRectificationArr[$oldValue];
                                }
                                else if($newKey === 'acquisitionTerms'){
                                    $acquisitionTermsArr = array('1' =>'1% gebyr','2'=>'2% gebyr');
                                    $newValue = $filterUpdateValueDataArray[$newKey];
                                    if($newValue == ''){$newValue = 1;}
                                    $filterUpdateValueDataArray[$newKey] = $acquisitionTermsArr[$newValue];
                                    //Update old values array
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    if($oldValue == ''){$oldValue = 1;}
                                    $filterOldValueDataArray[$newKey] = $acquisitionTermsArr[$oldValue];
                                }
                                else if($newKey === 'redemptionTerms')
                                {
                                    $newValue = (int)$newValue;
                                    if($newValue === 0)
                                        $filterUpdateValueDataArray[$newKey] = 'Vælg';
                                    else if($newValue === 1)
                                        $filterUpdateValueDataArray[$newKey] = 'Straksindfrielse';
                                    else if($newValue === 3)
                                        $filterUpdateValueDataArray[$newKey] = '3 måneder løbende';								
                                    $oldValue = $filterOldValueDataArray[$newKey];
                                    $oldValue = (int)$oldValue;
                                    if($oldValue === 0)
                                        $filterOldValueDataArray[$newKey] = 'Vælg';
                                    else if($oldValue === 1)
                                        $filterOldValueDataArray[$newKey] = 'Straksindfrielse';
                                    else if($oldValue === 3)
                                        $filterOldValueDataArray[$newKey] = '3 måneder løbende';
                                }
                                //Get investor name
                                else if(isset($filterUpdateValueDataArray['investorId']) && $filterUpdateValueDataArray['investorId']!='')
                                {
                                    $newInvestorName = $em->getRepository('Admin\Entity\Investors')->getInvestorNameByInvestorId($filterUpdateValueDataArray['investorId']);
                                    $oldInvestorName = $em->getRepository('Admin\Entity\Investors')->getInvestorNameByInvestorId($filterOldValueDataArray['investorId']);
                                    unset($filterUpdateValueDataArray['investorId']);
                                    unset($filterOldValueDataArray['investorId']);	
                                    if(isset($newInvestorName) && $newInvestorName!='')
                                    {
                                        $filterUpdateValueDataArray['investorName'] = $newInvestorName;
                                        $filterOldValueDataArray['investorName'] = $oldInvestorName;
                                    }

                                }

                                // Array will be used for formating in case history
                                $priceFormatedFieldArr = array('loanFinancingNeed','handlingFee','adjustedHandlingFee','tradingCost',
                                    'outlayCredit','dutyRedemption','deedCreationFee','deedFee',
                                    'discount1','discount2');
                                $durationYearsFormatedFieldArr = array('durationYears');
                                $rentPercentageFormatedFieldArr = array('fixedInterest','flexibleInterest',
                                    'referenceRateValue','rateSupplement');
                                $redemptionRateFormatedFieldArr = array('redemptionRate,expirationRedemptionRate');

                                if(in_array($newKey,$priceFormatedFieldArr))
                                {   
                                    if(!isset($filterUpdateValueDataArray[$newKey]) || $filterUpdateValueDataArray[$newKey]=='')
                                    {
                                         $filterUpdateValueDataArray[$newKey]=0;	
                                    } 
                                    $filterUpdateValueDataArray[$newKey] = @number_format($filterUpdateValueDataArray[$newKey],0,",",".")."kr.";

                                    if(!isset($filterOldValueDataArray[$newKey]) || $filterOldValueDataArray[$newKey]=='')
                                    {
                                        $filterOldValueDataArray[$newKey]=0;
                                    }                                
                                    $filterOldValueDataArray[$newKey] = @number_format($filterOldValueDataArray[$newKey],0,",",".");
                                }
                                elseif(in_array($newKey,$durationYearsFormatedFieldArr))
                                {
                                    $filterUpdateValueDataArray[$newKey] = $filterUpdateValueDataArray[$newKey]."år.";								
                                    $filterOldValueDataArray[$newKey] = $filterOldValueDataArray[$newKey];
                                }
                               
                                elseif(in_array($newKey,$rentPercentageFormatedFieldArr))
                                {
                                    $filterUpdateValueDataArray[$newKey] = str_replace(".",",",$filterUpdateValueDataArray[$newKey]);					
                                    $filterUpdateValueDataArray[$newKey] = $filterUpdateValueDataArray[$newKey]."%";								
                                    $filterOldValueDataArray[$newKey] = $filterOldValueDataArray[$newKey]*1;
                                    $filterOldValueDataArray[$newKey] = str_replace(".",",",$filterOldValueDataArray[$newKey]);
                                    $filterOldValueDataArray[$newKey] = $filterOldValueDataArray[$newKey];

                                }
                                elseif(in_array($newKey,$redemptionRateFormatedFieldArr))
                                {
                                    $filterUpdateValueDataArray[$newKey] = str_replace(".",",",$filterUpdateValueDataArray[$newKey]);
                                    $filterOldValueDataArray[$newKey] = str_replace(".",",",$filterOldValueDataArray[$newKey]);
                                    $filterOldValueDataArray[$newKey] = $filterOldValueDataArray[$newKey];
                                }
                            }					
                            //end
                            $em->getRepository('Admin\Entity\Casehistory')->logCasehistoryDataFromApplication($filterUpdateValueDataArray,$filterOldValueDataArray,$caseId,$userId,$username,$newUpdateCounter,$loanId);
                            if(isset($updateValueDataArray['fixedInterest']))
                            {
                               $updateValueDataArray['currentInterestRate'] = $updateValueDataArray['fixedInterest'];
                            } 
                            else if(isset($updateValueDataArray['flexibleInterest']))
                            {
                                $updateValueDataArray['currentInterestRate'] = $updateValueDataArray['flexibleInterest'];
                            }
                            
                            if(isset($updateValueDataArray['updateCounter'])){
                                unset($updateValueDataArray['updateCounter']);
                            }
                             //Update only if rate type change and not set fixedKurs and flexibleKurs
                            if(isset($updateValueDataArray['rateType']) && $updateValueDataArray['rateType']!='' && !isset($updateValueDataArray['fixedKurs']) && !isset($updateValueDataArray['flexibleKurs'])){
                                //Update only if rate type change and not set fixedKurs and flexibleKurs
                                if(trim(strtolower($updateValueDataArray['rateType'])) == 'fast'){
                                    $updateValueDataArray['exchangeRate'] = $fixedKurs; 
                                }  
                                else{
                                    $updateValueDataArray['exchangeRate'] = $flexibleKurs; 
                                } 
                            }
                            $em->getRepository('Admin\Entity\Loans')->updateLoansDataByloanId($updateValueDataArray,$loanId);
                            //Update case update counter 
                            $formUpdateCounter = $newUpdateCounter-1;
                            $em->getRepository('Admin\Entity\Cases')->updateCaseCounterByCaseId($caseId,$formUpdateCounter);
                        }
                        else
                        {   //Show warning in case of multiple user update
                            $errorsField = implode(",",$returnResponse['errorFields']);
                            $result =  array('response'=>"multiuserupdate", 'message'=>"Following fields ".$errorsField." are updated by some other users. Your updated are not saved.Please update your changes again!",'loanId'=>$loanId);
                            echo json_encode($result);
                            exit(); 
                        }
                    }	
                }
                else
                {
                    $arrMessages = $loansFormObj->getMessages();
                    $errors ='';
                    foreach($arrMessages as $key=>$value){
                        $errors.=$key.":".$value['isEmpty']." ";
                    }
                    $result =   array( 'response'=>"noupdate", 'message'=>"Form have some errors ".$errors,'loanId'=>$loanId );
                    echo json_encode($result);
                    exit(); 
                }	
            }

            if($this->isDataupdated == 1){	
                //Get tottal finanacing need ammount for all loans w.r.t case
                $sumOfAllLoansFinancingNeedAmount = $em->getRepository('Admin\Entity\Loans')->getTotlalFinancingNeedByCaseId($caseId);
                $sumOfAllLoansFinancingNeedAmount = number_format($sumOfAllLoansFinancingNeedAmount,0,",",".");                    
                $result =   array( 'response'=>"success", 'message'=>"stdDisplayMsg.LOAN_RECORD_UPDATE_MSG",'loanId'=>$loanId,'sum_of_all_loans_financing_need_amount'=>$sumOfAllLoansFinancingNeedAmount);
            }
            else		
                $result =   array( 'response'=>"noupdate", 'message'=>"stdDisplayMsg.LOAN_NO_RECORD_UPDATE_MSG",'loanId'=>$loanId );	
            
        } 		
        echo json_encode($result);
        exit();   
    }
	
    /*
    * Set number format of price
    * @param $value,$decimalPlace
    * @return int
    * @author shikha
    */
    public function setPriceNumberformat($value,$decimalPlace=0)
    {
        return number_format($value,$decimalPlace,",",".");
    }
	
    /*
    * Function check fields have update permission
    * @param $caseId,updated $fieldsArr ,form hidden field counter & latest db counter
    * @author shikha
    */
    public function checkFieldsUpdatePermission($fieldsArr=array(),$caseId,$caseFormCounter,$latestUpdateCounter)
    {   
        
        $fieldsKeyArray =  array_keys($fieldsArr);
        $em = $this->getEntityManager();		
        if((int)$latestUpdateCounter === (int)$caseFormCounter){
            return array('response'=>'success');
        }
        else
        {  
            $caseHistoryUpdateFields = $em->getRepository('Admin\Entity\Casehistory')->checkCaseHistoryUpdatedFields($fieldsKeyArray,$caseId,$caseFormCounter);		
            if(empty($caseHistoryUpdateFields))
                return array('response'=>'success');
            else
            {
                $caseHistoryUpdateFields = array_unique($caseHistoryUpdateFields);
                return array('response'=>'fail','errorFields'=>$caseHistoryUpdateFields);
            }
        }
    }
    
     /*
    * function get selected loans details by loan id and render view dynamically 
    * @param N/A
    * @return view variables array
    * @author shikha
    */
    public function getselectedloanbyloanidAction()
    {		
        $this->layout('layout/ajaxlayout');		
        $admin_session = new Container('Uobmember');
        $username = $admin_session->userName;
        if ($username =='') { 		
            return $this->redirect()->toRoute('admin');
        }
        $userId = $admin_session->userId;		
        //Controller pulgin object
        $cntPulginObj = $this->FormattingValuePlugin();
        $em = $this->getEntityManager();
        $request  = $this->getRequest(); 	
        $postData = $request->getPost();	
        $loanId  = $this->params('first_param');
        $caseId  = $this->params('second_param');   
        $em = $this->getEntityManager();      
        //Get Investor list	
        $investorNonConsumerList = $em->getRepository('Admin\Entity\Investors')->getInvestorListByLoanTypeId('1');
        $investorConsumerList = $em->getRepository('Admin\Entity\Investors')->getInvestorListByLoanTypeId('2');
        
                
        //Get all case loans        
        $caseLoanDetail = array();        
        if(!empty($loanId)){
            $caseLoanDetail = $em->getRepository('Admin\Entity\Loans')->getLoanDetailsByloanId($loanId);          
        }
        
        //Get Admin setting data for loan calculation
        $adminVariable = $em->getRepository('Admin\Entity\CalcMeta')->getAdminVariableSetting();
        // code for get the ownership change data.
        $ownershipchangeData = $em->getRepository('Admin\Entity\Ownershipchange')->getOwnershipchangeData();
        $showCaseId =10000+$caseLoanDetail[0]['caseId'];
        if($caseLoanDetail[0]['loanId'] !=='UOB#'.$showCaseId.'-001'){
            $loanTypeData = $em->getRepository('Admin\Entity\LoanType')->getloanTypeListData();   // loans other them main loan           
        }else{
            $loanTypeData = $em->getRepository('Admin\Entity\LoanType')->getMainLoanTypeListData(); 
        }
        if(strtoupper($caseLoanDetail[0]['loanType'])=='FORBRUGER'){  // CONSUMER
            $investorList = $investorConsumerList;
        }else{        
            $investorList =  $investorNonConsumerList;
        }
        
        $documentexists='';
        $isDocExist = $em->getRepository('Admin\Entity\ClientInvoices')->getClientInvoicesByloanId($loanId);
        if(!empty($isDocExist)) {
            $documentexists='exists'; 
        }
        
        
        $caseCreditpurpose = $em->getRepository('Admin\Entity\Cases')->getCaseCreditPurposeWrtCaseId($caseId);
        $wiped = $em->getRepository('Admin\Entity\Cases')->getWipedDataBycaseId($caseId);
        //code for get Repayment profile Data.
        $repaymentProfileMasterData = $em->getRepository('Admin\Entity\RepaymentProfileMaster')->getRepaymentProfileMasterData(); 
        $loansFormObj = new LoansForm(null,array(),array('investorId'=>$investorList,'ownershipchangeData'=>$ownershipchangeData,'loanTypeData'=>$loanTypeData,'repaymentProfileMasterData'=>$repaymentProfileMasterData,'documentexists'=>$documentexists,'caseCreditpurpose'=>$caseCreditpurpose,'wiped'=>$wiped['wiped']));
        //Session container
        $agent_session = new Container('Uobmember');
        $userTypeKey = $agent_session->userTypeKey;
        if($userTypeKey != $this->adminType)
        {
            //Get Group stage
            $caseStageArr = $em->getRepository('Admin\Entity\Cases')->getCaseStageData($caseId);
            $stage = ucfirst($caseStageArr[0]['stage']);
            $groupStage = $em->getRepository('Admin\Entity\Casestages')->getGroupStage($stage);
            //Get all Permission fields
            $allPermissionFields = $em->getRepository('Admin\Entity\Permissions')->getPermissionFields($userTypeKey,$this->casesTable,$groupStage);
            if(!empty($allPermissionFields))
            {		
                foreach($allPermissionFields as $fKey => $fvalue)
                {
                    $elementExist = $loansFormObj->hasAttribute($fKey);
                    if(isset($elementExist) && $fvalue==0)				
                        $loansFormObj->get($fKey)->setAttribute('readonly', 'readonly');
                }
            }
        }	
        //END		
        //Set form text fields value  
        $formDataArr = array();	
        $formDataArr = $caseLoanDetail[0]; 
        if(isset($caseLoanDetail[0]['payoutDate']))
        {
            
            $payoutDate = $caseLoanDetail[0]['payoutDate']->format('d.m.Y');
            $payoutDate = $cntPulginObj->dateFormater($payoutDate,'DD.MM.YYYY');
        }
        else
        {
            $payoutDate = '';
        }      
        
        //referenceRateType
        if(!isset($caseLoanDetail[0]['referenceRateType']) OR $caseLoanDetail[0]['referenceRateType']=='')
        {
            $caseLoanDetail[0]['referenceRateType'] = 'CIBOR12';
            $formDataArr['referenceRateType'] ='CIBOR12';
        }

        $formDataArr['payoutDate'] = $payoutDate;		
        if(isset($caseLoanDetail[0]['loanDate']))
        {
            $loanDate = $caseLoanDetail[0]['loanDate']->format('d.m.Y');
            $loanDate = $cntPulginObj->dateFormater($loanDate,'DD.MM.YYYY');
        }
        else
        {
            $loanDate='';
        }						
        $formDataArr['loanDate'] = $loanDate;	
        if(isset($caseLoanDetail[0]['secondPeriodInterestRate']) && $caseLoanDetail[0]['secondPeriodInterestRate'] !=''){
            $caseLoanDetail[0]['secondPeriodInterestRate'] = $caseLoanDetail[0]['secondPeriodInterestRate'];
        }else{
            $caseLoanDetail[0]['secondPeriodInterestRate'] =$adminVariable['FOURSTEPFIXEDRATERENTE'];
        }
        if(isset($caseLoanDetail[0]['saleDate']))
        {
            $saleDate = $caseLoanDetail[0]['saleDate']->format('d.m.Y');
            $saleDate = $cntPulginObj->dateFormater($saleDate,'DD.MM.YYYY');
        }
        else
        {
            $saleDate='';
        }						
        $formDataArr['saleDate'] = $saleDate;	
        
        if(isset($caseLoanDetail[0]['salesInterestFrom']))
        {
            $salesInterestFrom = $caseLoanDetail[0]['salesInterestFrom']->format('d.m.Y');
            $salesInterestFrom = $cntPulginObj->dateFormater($salesInterestFrom,'DD.MM.YYYY');
        }
        else
        {
            $salesInterestFrom ='';
        }
        $formDataArr['salesInterestFrom'] = $salesInterestFrom;
        
        
        //Change redemption rate display value format
        if(isset($caseLoanDetail[0]['redemptionRate']))
        {
            $redemptionRate = $caseLoanDetail[0]['redemptionRate'];//$cntPulginObj->setPriceformat($caseLoanDetail[0]['redemptionRate'],0);
            $redemptionRate = str_replace(".",",",$caseLoanDetail[0]['redemptionRate']);
        }
        else
        {
            $redemptionRate = '';
        }						
        $formDataArr['redemptionRate'] = $redemptionRate;
        
        //Condition For Sale Rate for first loan created with Four Step Form as per bug UoB-49876
        if(isset($caseLoanDetail[0]['salesRate']) && $caseLoanDetail[0]['salesRate']== '0.00')
        {
            $caseLoanDetail[0]['salesRate'] = '';
        }
        $formDataArr['salesRate'] = $caseLoanDetail[0]['salesRate'];
        $formDataArr['firstPeriodTermsNumber'] = $caseLoanDetail[0]['firstPeriodTermsNumber'];
        $formDataArr['secondPeriodInterestRate'] = $caseLoanDetail[0]['secondPeriodInterestRate'];
           
        //Get Property Id
         $propertyId = $em->getRepository('Admin\Entity\CaseLoanProperties')->getCaseLoanActivePropertyIdByLoanId($loanId);   

        //Get Valuation of property (Latest value assessment)
        
        $realpropertyvalueassessment = 0;	
        $idqValueAssessmentData = $em->getRepository('Admin\Entity\IdqValueassessment')->getIdqValueAssessmentDataByForDocumentPropertyId($propertyId);
        if(!empty($idqValueAssessmentData))
        {
            $realpropertyvalueassessment = $idqValueAssessmentData[0]['realpropertyvalueassessment'];
        }   
        
        #######################################################################
        //Mail To Link Code  Start
        $caseData  = $em->getRepository('Admin\Entity\Cases')->getDefaultCaseDataByCaseId($caseId);
       
        //Check Case have single loans or multi loan option  added on 8-Dec-16 to syn aggrigate financing need value 
        $updatedLoanFinancingNeedAmount = 0;
        $isPrimaryLoanFinancingNeedUpdateRequire = 0;       
        if((int)$caseData[0]['creditPurpose']===1)
        {
            
            $loanIdStr = $caseLoanDetail[0]['loanId'];
            $primaryLoanCounterArr = explode("-",$loanIdStr);
            $loanNumber = end($primaryLoanCounterArr);  
            //n multiloan case
            if((int)$loanNumber===1)
            {
                $currentloanFinancingNeedValue = $caseLoanDetail[0]['loanFinancingNeed'];
                $sumOfAllLoansFinancingNeedAmount = $em->getRepository('Admin\Entity\Loans')->sumOfAllLoansFinancingNeedByCaseId($caseId);
                $actualFinanacingNeedValue = $caseData[0]['expectedPropertyCost'] - $caseData[0]['deposit'];
                $updatedLoanFinancingNeedAmount =  $actualFinanacingNeedValue - $sumOfAllLoansFinancingNeedAmount;
                if((int)$updatedLoanFinancingNeedAmount!==0)
                {
                    $isPrimaryLoanFinancingNeedUpdateRequire = 1;  
                    $updatedLoanFinancingNeedAmount = $currentloanFinancingNeedValue + $updatedLoanFinancingNeedAmount;
                    
                } 
            }       
        }
        else {
            
            //UOB#19355-001
            $loanIdStr = $caseLoanDetail[0]['loanId'];
            $primaryLoanCounterArr = explode("-",$loanIdStr);
            $loanNumber = end($primaryLoanCounterArr);  
            //n multiloan case
            if((int)$loanNumber===1)
            {
                $currentloanFinancingNeedValue = $caseLoanDetail[0]['loanFinancingNeed'];
                $sumOfAllLoansFinancingNeedAmount = $em->getRepository('Admin\Entity\Loans')->sumOfAllLoansFinancingNeedByCaseId($caseId);
                $actualFinanacingNeedValue = $caseData[0]['financingNeed'];
                $updatedLoanFinancingNeedAmount =  $actualFinanacingNeedValue - $sumOfAllLoansFinancingNeedAmount;
                if((int)$updatedLoanFinancingNeedAmount!==0)
                {
                    $isPrimaryLoanFinancingNeedUpdateRequire = 1;  
                    $updatedLoanFinancingNeedAmount = $currentloanFinancingNeedValue + $updatedLoanFinancingNeedAmount;
                } 
            }
        }
        
        //Get Priority value
        if(isset($caseLoanDetail[0]['priority']) && $caseLoanDetail[0]['priority'] != '') {  
            $priorityValue = $caseLoanDetail[0]['priority'];
        } else{
            $priorityValue = " ";
        }
        
        $investorId = $caseLoanDetail[0]['investorId'];
        if($investorId!=''){
            $investorData = $em->getRepository('Admin\Entity\Investors')->getInvestorDetailByInvestorId($investorId);
            $casePersonData = $em->getRepository('Admin\Entity\CasePerson')->getAllPersonsDataByCaseId($caseId);            
            //Get Active Property Data
            
            $casePropertiesData = $em->getRepository('Admin\Entity\CaseProperties')->getCPLAllpropertiesDataByCaseId($caseId,$loanId);
            
            $resultDataArr = array();
            //Get Investor Email
            if(isset( $investorData[0]['email']) &&  $investorData[0]['email'] != ''){
                $investorEmail =  $investorData[0]['email'];
            } else{
                $investorEmail = ' ';
            }
            $resultDataArr['investorEmail'] = $investorEmail;

            //Get Interest value
            if(isset($caseLoanDetail[0]['rateType']) && $caseLoanDetail[0]['rateType'] == 'Fast'){
                $interestvalue = $cntPulginObj->setPriceformat($caseLoanDetail[0]['fixedInterest'],2);
                $interestvalue = $interestvalue."%";
                $interestvaluesubject =  $interestvalue;
            } else{
                $interestvalue = $cntPulginObj->setPriceformat($caseLoanDetail[0]['flexibleInterest'],2);
                $interestvalue = $interestvalue."%";
                $interestvaluesubject = 'Cibor12+'.$interestvalue;
            }

            //Get Loan amount value
            if(isset($caseLoanDetail[0]['loanAmount']) && $caseLoanDetail[0]['loanAmount'] != '') {  
                $loanAmountValue = $caseLoanDetail[0]['loanAmount'];
                $loanAmountValue = $cntPulginObj->setPriceformat($loanAmountValue,2);
                $loanAmountValue = $loanAmountValue." kr.";
            } else{
                $loanAmountValue = " ";
            }
            
            //Get active property address
            if(isset($casePropertiesData[0]['propertyAddress']) && $casePropertiesData[0]['propertyAddress'] != '') {  
                $propertyAddress = $casePropertiesData[0]['propertyAddress'];
            } else{
                $propertyAddress = "";
            }

            if(!isset($investorData[0]['contacts']) || $investorData[0]['contacts'] == ''){
                $resultDataArr['Kære'] = "alle";
            } 
            else {
                $resultDataArr['Kære'] =  $investorData[0]['contacts'];
            }

            //Get Property Address
            $resultDataArr['propertyAddress'] = $propertyAddress;

            //Get Credit Purpose text
            if(isset($caseData[0]['creditPurpose']) && $caseData[0]['creditPurpose'] == '1') {  
                $creditPurposeText = "køb af en ejendom";
            } else{
                $creditPurposeText = "en tillægsbelåning";
            }
            $resultDataArr['creditPurpose'] = $creditPurposeText;

            //Get count number of people assigned to case - non-guarantors
            $countPeopleNonGuarantor = $em->getRepository('Admin\Entity\CasePerson')->countPeopleNonGuarantorByCaseId($caseId);
            // Get count number of guarantors w.r.t case id.
            $countTotalGuarantor = $em->getRepository('Admin\Entity\CasePerson')->countGuarantorByCaseId($caseId);
            $resultDataArr['personGuarantorCnt'] = $countTotalGuarantor;
            if($countTotalGuarantor > 0){
                $resultDataArr['personGuarantorCnt'] = " og ".$countTotalGuarantor." kautionister";
            }else{
                $resultDataArr['personGuarantorCnt'] = "";
            }
            //Person Info Section
            $personInfo = array();
            $personInfoForMail = array();
            $personInfoForMailStr='';
            foreach($casePersonData as $cpData)
            {   
                //Person Full Name
                if(isset($cpData['personFullName']) && $cpData['personFullName'] != ''){
                    $personFullName = "<b>".$cpData['personFullName']."</b>";
                } else{
                    $personFullName = '';
                }
                //Birth Date
                if(isset($cpData['birthDate']) && $cpData['birthDate'] != ''){
                    $birthDate = $cpData['birthDate']->format('d.m.Y');
                    $birthDate = $cntPulginObj->dateFormater($birthDate,'DD-MM-YYYY');
                    $birthDate = $birthDate;
                } else{
                    $birthDate = '';
                }
                //MoveIn Date
                if(isset($cpData['moveInDate']) && $cpData['moveInDate'] != ''){
                    $moveInDate = $cpData['moveInDate']->format('d.m.Y');
                    $moveInDate = $cntPulginObj->dateFormater($moveInDate,'DD-MM-YYYY');
                    $moveInDate = $moveInDate;
                } else{
                    $moveInDate = '';
                }
                //Street Name
                if(isset($cpData['streetName']) && $cpData['streetName'] != ''){
                    $streetName = $cpData['streetName'];
                } else{
                    $streetName = '';
                }
                //Street Identifier
                if(isset($cpData['streetIdentifier']) && $cpData['streetIdentifier'] != ''){
                    $streetIdentifier = $cpData['streetIdentifier'];
                } else{
                    $streetIdentifier = '';
                }
                //Floor Identifier
                if(isset($cpData['floorIdentifier']) && $cpData['floorIdentifier'] != ''){
                    $floorIdentifier = $cpData['floorIdentifier'];
                }else{
                    $floorIdentifier = '';
                }
                //Suite Identifier
                if(isset($cpData['suiteIdentifier']) && $cpData['suiteIdentifier'] != ''){
                    $suiteIdentifier = $cpData['suiteIdentifier'];
                }else{
                    $suiteIdentifier = '';
                }
                //Post Code Identifier
                if(isset($cpData['postCodeIdentifier']) && $cpData['postCodeIdentifier'] != ''){
                    $postCodeIdentifier = $cpData['postCodeIdentifier'];
                } else{
                    $postCodeIdentifier = '';
                }
                //District Name
                if(isset($cpData['districtName']) && $cpData['districtName'] != ''){
                    $districtName = $cpData['districtName'];
                } else{
                    $districtName = '';
                }
                //Get Owned property Data
                if(isset($cpData['id']) && $cpData['id'] != ''){
                    $ownedPropertyData = $em->getRepository('Admin\Entity\OwnedProperty')->getOwnedPropertyByPersonId($cpData['id']);
                    if(!empty($ownedPropertyData)){                  
                        $checkownedproperties = "Debitor ejer følgende ejendomme: ";   
                        $ownPlpCnt = 0;
                        foreach($ownedPropertyData as $ownedPropertyAddress)
                        {
                            if ($ownPlpCnt == 0) {
                                $checkownedproperties .= $ownedPropertyAddress['propertyAddress'];
                            } 
                            else
                            {
                                $checkownedproperties .= ", ".$ownedPropertyAddress['propertyAddress'];
                            }    
                            // …
                            $ownPlpCnt++;                        
                        } 
                    } else{
                        $checkownedproperties =  "Debitor bor i dag til til leje";
                    }
                }
                $address='';
                if($streetName !=''){ $address =$streetName; }
                if($streetIdentifier !=''){ if($address ==''){ $address = $streetIdentifier; }else{  $address.=" ".$streetIdentifier; }}
                if($floorIdentifier !=''){ if($address ==''){ $address = $floorIdentifier; }else{  $address.=" ".$floorIdentifier; }}
                if($suiteIdentifier !=''){ if($address ==''){ $address = $suiteIdentifier; }else{  $address.=" ".$suiteIdentifier; }}
                if($postCodeIdentifier !=''){ if($address ==''){ $address = $postCodeIdentifier; }else{  $address.=" ".$postCodeIdentifier;} }
                if($districtName !=''){ if($address ==''){ $address = $districtName; }else{  $address.=" ".$districtName; }}

                $personInfoForMailStr .= "\r\n\r\n".$personFullName;
                if(trim($birthDate)!='')
                    $personInfoForMailStr .= " født ".$birthDate;
                if(trim($moveInDate)!='')
                    $personInfoForMailStr .= " bor siden ".$moveInDate;
                if(trim($address)!='')
                    $personInfoForMailStr .= " ".$address.".";
                if(trim($checkownedproperties)!='')
                    $personInfoForMailStr .= " ".$checkownedproperties.".";            
                $personInfoForMailStr .= " Debitor har en brutto løn på XXX kr. pr. måned. Debitor arbejder med XXX.";

            }

            //Get Idq Data
            if(!empty($casePropertiesData[0]['propertyId'])){
                    $idqPropertyData = $em->getRepository('Admin\Entity\Idq')->getIDQByCaseId($casePropertiesData[0]['propertyId']);
                    if(!empty($idqPropertyData)){
                        $dwellingArea = $idqPropertyData[0]['dwellingarea'];
                        if(isset($dwellingArea) && $dwellingArea!='')
                            $dwellingArea =  $cntPulginObj->setPriceformat($dwellingArea,0);                    
                        $ladnParcelAreaMeasure = $idqPropertyData[0]['ladnparcelareameasure'];
                        if(isset($ladnParcelAreaMeasure) && $ladnParcelAreaMeasure!='')
                            $ladnParcelAreaMeasure = $cntPulginObj->setPriceformat($ladnParcelAreaMeasure,0);
                    } else{
                        $dwellingArea = "";
                        $ladnParcelAreaMeasure = "";
                    }
            } 
            else{
                $dwellingArea = "";
                $ladnParcelAreaMeasure = "";
            }
            //Get Idq Building Number one Data
            if(!empty($casePropertiesData[0]['propertyId'])){
                $idqBuilding1Data = $em->getRepository('Admin\Entity\IdqBuilding')->getIdqbuildingNumberOneDataByPropertyId($casePropertiesData[0]['propertyId']);
                if(!empty($idqBuilding1Data)){
                    $constructionyear = $idqBuilding1Data[0]['constructionyear'];
                    $roofingmaterial = $idqBuilding1Data[0]['roofingmaterial'];
                    $heatinginstallation = $idqBuilding1Data[0]['heatinginstallation'];
                } else{
                    $constructionyear = "";
                    $roofingmaterial = "";
                    $heatinginstallation = "";
                }
            } 
            else
            {
                $constructionyear = "";
                $roofingmaterial = "";
                $heatinginstallation = "";
            }
            
            if(isset($caseData[0]['creditPurpose']) && $caseData[0]['creditPurpose'] == '1'){
                //Display in variable section
                //Display in mail section
                $expectedPropertyCost = $caseData[0]['expectedPropertyCost'];
                $expectedPropertyCost = $cntPulginObj->setPriceformat($expectedPropertyCost,2);

                $depositCost = $caseData[0]['deposit'];
                $depositCost = $cntPulginObj->setPriceformat($depositCost,2);
                $resultDataArr['creditPurposeDataText'] = "Ejendommens handelspris er ".$expectedPropertyCost."kr. med en udbetaling på ".$depositCost."kr.";
            } else{
                //Display in variable section
                //Display in mail section
                $resultDataArr['creditPurposeDataText'] = "Ejendommens værdi vurderes at være XXX kr.";
            }
            //Calculation for safety limit term
            $loanAmount = 0;
            if(isset($caseLoanDetail[0]['loanAmount']) && $caseLoanDetail[0]['loanAmount']!= '')
            {
                $loanAmount = $caseLoanDetail[0]['loanAmount'];
            }
            if(!empty($casePropertiesData[0]['propertyId'])){
                $safetyPercentage = $casePropertiesData[0]['anteriorDebt'] + $loanAmount;
            } else{
                $safetyPercentage =  $loanAmount;
            }
            //Get safety limit
            if($caseData[0]['creditPurpose'] == 1)
            {
                $safetyPercentageDivider = $caseData[0]['expectedPropertyCost'];
            }
            else
            {
                $safetyPercentageDivider = 0;
                //Get Real Property Value Assesment For All Active Properties
                if(!empty($casePropertiesData[0]['propertyId'])){
                    $propertyAssessmentAllProperties = array();
                    $activePropertyId = $casePropertiesData[0]['propertyId'];
                    $propertyAssessmentActiveProperties = $em->getRepository('Admin\Entity\IdqValueassessment')->getIdqValueAssessmentDataByCaseId($activePropertyId);
                    if(!empty($propertyAssessmentActiveProperties))
                    {
                        $propertyAssessmentAllProperties[$activePropertyId] = $propertyAssessmentActiveProperties[0]['realpropertyvalueassessment'];
                    } else {
                        $propertyAssessmentAllProperties[$activePropertyId] = array();
                    }
                    if(isset($propertyAssessmentAllProperties[$casePropertiesData[0]['propertyId']]) && !empty($propertyAssessmentAllProperties[$casePropertiesData[0]['propertyId']])){ 
                        $safetyPercentageDivider = $propertyAssessmentAllProperties[$casePropertiesData[0]['propertyId']];
                    } else { 
                        $safetyPercentageDivider = 0;
                    } 
                } 
                else 
                {
                    $safetyPercentageDivider = 0;
                }
            }
            if($safetyPercentageDivider == 0) { 
                $safetyPercentage = 0;
            }
            else 
            {
                $safetyPercentage = $safetyPercentage/$safetyPercentageDivider;
            }
            $safetyPercentage = ($safetyPercentage*100);
            $safetyPercentage = $cntPulginObj->setPriceformat($safetyPercentage,2)."%";
            //Get Anterior Debt
            if(!empty($casePropertiesData[0]['propertyId'])){
                $anteriorDebt = $casePropertiesData[0]['anteriorDebt'];
                $anteriorDebt = $cntPulginObj->setPriceformat($anteriorDebt,2)."kr.";
            } 
            else
            {
                $anteriorDebt =  "";
            }
            //Get Sales Rate
            if(isset($caseLoanDetail[0]['salesRate']) && $caseLoanDetail[0]['salesRate'] != ''){
                $salesRate = $cntPulginObj->setPriceformat($caseLoanDetail[0]['salesRate'],2);
            } else{
                $salesRate = " ";
            }

            $resultDataArr['salesRate'] = $salesRate;

            $propertyAddressSubject ='';
            $spacePropertyAddress ='';
            if($propertyAddress !==''){
                $propertyAddressSubject =$propertyAddress." - ";
                $spacePropertyAddress   =" ".$propertyAddress;
            }
            $priorityValueMessage = '';
            $cpmData = $em->getRepository('Admin\Entity\CaseLoanProperties')->getCaseLoanPropertyData($caseId,$loanId,$propertyId);
            if(!empty($cpmData)){
               $priorityValueMessage = $cpmData['priority']; 
            }
            
            $mailSubjectContent = "K - ".$caseLoanDetail[0]['loanId']." - ".$propertyAddressSubject.$priorityValueMessage.". prioritet - hovedstol ".$loanAmountValue." - ".$interestvaluesubject;
            $mailSubjectContentForJsUse =  json_encode($mailSubjectContent);
            $mailSubjectContent = urlencode($mailSubjectContent);
            $mailSubjectContent = str_replace('+','%20',$mailSubjectContent);           

            $mailBodyContent = "Kære ".$resultDataArr['Kære']."\r\nLad mig venligst høre om nedenstående har jeres interesse.\r\n\r\nBeskrivelse\r\nSagen drejer sig om finansiering af ejendommen".$spacePropertyAddress.". Lånet udbetales i forbindelse med ".$resultDataArr['creditPurpose'].". Der ansøges om en hovedstol på ".$loanAmountValue." med ".$priorityValueMessage.". prioritet i ejendommen. Der er ".$countPeopleNonGuarantor." debitorer på lånet".$resultDataArr['personGuarantorCnt'].". Lånet skal bruges til XXX.\r\n\r\nDebitor".$personInfoForMailStr."\r\n\r\nEjendommen\r\n\r\nEjendommen er beliggende".$spacePropertyAddress.". Boligen udgør ".$dwellingArea." kvm, beliggende på ".$ladnParcelAreaMeasure." kvm stor grund, ejendommen er opført i år ".$constructionyear.". Ejendommen er i XXX STAND. Jf. OIS har ejendommen følgende tagmateriale: ".$roofingmaterial." og opvarmes med: ".$heatinginstallation."\r\n\r\n".$resultDataArr['creditPurposeDataText']."\r\n\r\nLån\r\nHovedstol på ".$loanAmountValue." Nominel rente ".$interestvalue.". Den sikkerhedsmæssig placering er fra ".$anteriorDebt." - ".$safetyPercentage.".\r\n\r\nTilbud\r\nPantebrevet tilbydes til kurs ".trim($salesRate)."." ;
            $mailBodyContent = str_replace('  ',' ',$mailBodyContent);
            $mailBodyContent = str_replace('<b>','',$mailBodyContent);
            $mailBodyContent = str_replace('</b>','',$mailBodyContent);
            $mailBodyContentForJsUse =  json_encode($mailBodyContent);
            $mailBodyContent = urlencode($mailBodyContent);
            $mailBodyContent = str_replace('+','%20',$mailBodyContent);
           
        }
        else {
            $mailBodyContent = '';
            $mailSubjectContent = '';           
            $mailSubjectContentForJsUse = '';
            $mailSubjectContentForJsUse = json_encode($mailSubjectContentForJsUse);
            $mailBodyContentForJsUse = '';
            $mailBodyContentForJsUse = json_encode($mailBodyContentForJsUse);
            $investorEmail='';
        }
        //Mail To Link Code End here
        #######################################################################
        
        //Get Case Update Counter to show up to date indicator in header right corner
        $caseUpdateCounter = $em->getRepository('Admin\Entity\Cases')->getCaseUpdateCounter($caseId);      
        $formDataArr['updateCounter'] = $caseUpdateCounter['caseDataCounter'];
        $loansFormObj->setData($formDataArr);        
        
        //check is new loans or not
        //$isLoanNew = 0 laon already added,1->loan is new need to save 
        if(isset($caseLoanDetail[0]['loanAmount']) && (int)$caseLoanDetail[0]['loanAmount']>0 && (int)$caseLoanDetail[0]['settlementAmount']>0){
            $isLoanNew = 0;
        } 
        else{
           $isLoanNew = 1; 
        }
        
        //Check is economic document exist w.r.t case id
        $isIconomicDocExist = $em->getRepository('Admin\Entity\CaseEconomic')->isNonDeletedEconomicDocumentExist($caseId,$loanId);
        
        // Get consumer defalut values //
        $ResultCalculatorArray = $em->getRepository('Admin\Entity\Cases')->getAdminSettingForConsumer();
        $admin_variable = array();
        foreach($ResultCalculatorArray as $ResultCalculator) {
            $admin_variable[$ResultCalculator['calcKey']] = $ResultCalculator['calcValue'] ;
        }
        
        $officieltStempelafgift="";
        if(isset($caseLoanDetail[0]['loanAmount'])){
            if(isset($caseLoanDetail[0]['dutyRate']) && $caseLoanDetail[0]['dutyRate']!=""){
                $officieltStempelafgift = $caseLoanDetail[0]['loanAmount']*$caseLoanDetail[0]['dutyRate'];
                $officieltStempelafgift = $this->roundNearestHundredUp($officieltStempelafgift);
                $officieltStempelafgift = $cntPulginObj->setPriceformat($officieltStempelafgift,2)."kr.";
            }else{
                $officieltStempelafgift = $caseLoanDetail[0]['loanAmount'];
                $officieltStempelafgift = $this->roundNearestHundredUp($officieltStempelafgift);
                $officieltStempelafgift = $cntPulginObj->setPriceformat($officieltStempelafgift,2)."kr.";
            }
        }
        
       $caseLoanDetail[0]['creditPurpose']= $caseData[0]['creditPurpose']; 
        return new ViewModel(array( 
            'caseDetail' => $caseLoanDetail,
            'caseUpdateCounter'=> $caseUpdateCounter,
            'caseId' => $caseId,
            'loanId' => $loanId,
            'adminVariable' => $adminVariable,
            'loanFormObj' => $loansFormObj,
            'cntPulginObj' => $cntPulginObj,
            'defaultInvestorId'=>$caseLoanDetail[0]['investorId'],
            'investorEmail'=>$investorEmail,
            'investorList'=>$investorList,
            'isLoanNew'=>$isLoanNew,
            'realPropertyValueAssessment'=>$realpropertyvalueassessment,
            'mailBodyContent'=>$mailBodyContent,
            'mailSubjectContent'=>$mailSubjectContent,
            'mailSubjectContentForJsUse'=>$mailSubjectContentForJsUse,
            'mailBodyContentForJsUse'=>$mailBodyContentForJsUse,
            'isFinancingNeedUpdateRequire'=>$isPrimaryLoanFinancingNeedUpdateRequire,            
            'updatedLoanFinancingNeedAmount'=>$updatedLoanFinancingNeedAmount,
            'isIconomicDocExist'=>$isIconomicDocExist,
            'investorNonConsumerList'=>$investorNonConsumerList,
            'investorConsumerList'=>$investorConsumerList,
            'consumerDefaultValue'=>$admin_variable,
            'documentexists'=>$documentexists,
            'officieltStempelafgift'=>$officieltStempelafgift
        ));	
    }
    
    public function roundNearestHundredUp($number){
        $round = round($number, -2);
        if($number > $round){ $round = $round + 100;}
        return $round;
    }
    
    /*
    * function to get kreditor details to show in kreditor tab.
    * @param investorId
    * @return json array
    * @author shikha
    */
    public function getLoansTabKreditorDetailsAction()
    {	
        $this->layout('layout/ajaxlayout');
        $request =	$this->getRequest(); 	
        $result = array('response' => 'error', 'message' => 'There was some error. Try again.');
        //Get agent details from session
        $agent_session 	= new Container('Uobmember');
        if($request->isXmlHttpRequest() && isset($agent_session->userId))
        {
            $postData 	 =	$request->getPost();
            $investorId  =	$postData['investorId'];
            $em = $this->getEntityManager();
            $investorDetails = $em->getRepository('Admin\Entity\Investors')->getInvestorDetailByInvestorId($investorId);
            $investorDataArray = array();
            if(!empty($investorDetails))
            {
                $investorDetails = $investorDetails[0];
                $name = $investorDetails['investorName'];
                $cprCvrNumber = $investorDetails['cprCvrNumber'];
                if($investorDetails['entityType']==0){
                    $investorType='Privat';
                }else{
                      $investorType='Erhverv';
                }             
                if(isset($investorDetails['address']) && $investorDetails['address']!='')
                    $address = $investorDetails['address'];
                else
                    $address = 'N/A';

                if(isset($investorDetails['phoneNumber']) && $investorDetails['phoneNumber']!='')
                    $phoneNumber = $investorDetails['phoneNumber'];
                else
                    $phoneNumber = 'N/A';				

                if(isset($investorDetails['email']) && $investorDetails['email']!='')
                    $email = $investorDetails['email'];
                else
                    $email = 'N/A';	

                if(isset($investorDetails['companyType']) && $investorDetails['companyType']!='')
                    $companyType = $investorDetails['companyType'];
                else
                    $companyType = 'N/A';	

                if(isset($investorDetails['investorCode']) && $investorDetails['investorCode']!='')
                    $companyCode = $investorDetails['investorCode'];
                else
                    $companyCode = 'N/A';

                $resultString ='
                    <li>
                        <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">Kreditortype</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$investorType.'</label>
                        <label class="col-md-3 col-sm-4 no-padding"> </label>
                        </div>
                    </li>
                    <li>
                        <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">Kreditorkode</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$companyCode.'</label>
                        <label class="col-md-3 col-sm-4 no-padding"> </label> 
                        </div>
                    </li>
                    <li>
                     <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">CVR/CPR nummer</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$cprCvrNumber.'</label>
                         <label class="col-md-3 col-sm-4 no-padding"> </label>
                         </div>
                    </li>
                    <li>
                     <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">Navn</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$name.'</label>
                        <label class="col-md-3 col-sm-4 no-padding"> </label>
                        </div>
                    </li>
                    <li>
                     <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">Adresse</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$address.'</label>
                        <label class="col-md-3 col-sm-4 no-padding"> </label>
                        </div>
                    </li>
                    <li>
                     <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">Kontaktemail</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$email.'</label>
                        <label class="col-md-3 col-sm-4 no-padding"> </label>
                        </div>
                    </li>
                    <li>
                     <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 no-padding">
                        <div class="pull-left col-md-5 col-sm-4 col-xs-12 col-lg-5 li-label">Kontakttelefonnummer</div>
                        <label class="col-md-4 col-sm-12 col-xs-12">'.$phoneNumber.'</label>                          
                        <label class="col-md-3 col-sm-4 no-padding"> </label>  
                        </div>
                    </li>
                    ';	
            }
            $result  = array('response'=>"success",'investorDetails'=>$resultString);
        }
        echo json_encode($result);
        exit();    
    }
    
    /*
    * Function use to get cvr api data
    * @param N/A
    * @return string
    * @author shikha
    */
    function cvrapi($vat, $country='dk')
    {
        //Strip all other characters than numbers
        $vat = preg_replace('/[^0-9]/', '', $vat);
        //Start cURL
        $ch = curl_init();
        //Set cURL options
        curl_setopt($ch, CURLOPT_URL, 'http://cvrapi.dk/api?search=' . $vat . '&country=' . $country);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'UoB');
        //Parse result
        $result = curl_exec($ch);
        //Close connection when done
        curl_close($ch);
        //Return our decoded result
        $decodResult = json_decode($result, 1);		
        return $decodResult;
    }
	
    /*
    Function : Get person details by person cpr api
    * @param  cpr number
    * @return person data array
    * @author shikha
    */
    function getPersonDetailsByCprApi($personCPRNumber)
    {
        $requestPersonCPRNumber = $personCPRNumber;
        $personCPRNumber = str_replace("-","",$personCPRNumber);
        //IDQ_Url define in local.php in config folder
        $url = IDQ_Url."&searchExpression=".$personCPRNumber;
        $url = str_replace( "&amp;", "&", urldecode(trim($url)) );
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url ); 
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );  
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
        $personResponseArray  = curl_exec( $ch );
        $response = curl_getinfo( $ch );
        curl_close ($ch);
        $personResponseArray =  json_decode($personResponseArray,true);	
        $personCPRData 		 =	array();
        $personCPRData['cprStatus'] = 0;	
        if(isset($personResponseArray['Addresses'][0]))
        {
            if(isset($personResponseArray['Addresses'][0]['Contacts'][0]))
            {
                $personResponseContactArray 	 = $personResponseArray['Addresses'][0]['Contacts'][0];	
                $personCPRData['name'] 			 = $personResponseContactArray['PersonNameForAddressingName'];
                if(!isset($personCPRData['name']) || $personCPRData['name']=='')
                {
                    $personCPRData['name'] 	=  $personResponseContactArray['PersonGivenName']." ".$personResponseContactArray['PersonSurnameName'];
                }				
                $personCPRData['personMoveInDate'] 	 = '';
                if(isset($personResponseContactArray['MoveInDate']) && $personResponseContactArray['MoveInDate']!='')
                {
                    $MoveInDate =  str_replace('/Date(','',$personResponseContactArray['MoveInDate']);
                    $MoveInDate =  str_replace(')','',$MoveInDate);
                    $MoveInDate =  str_replace('/','',$MoveInDate);
                    if($this->getTimeZoneFromString($MoveInDate)!='')
                        $MoveInDate = substr($MoveInDate,0,-5);

                    $MoveInDate = ( $MoveInDate / 1000 );
                    $personResponseContactArray['MoveInDate'] = date("Y-m-d", $MoveInDate);
                    $personCPRData['personMoveInDate']	=	$personResponseContactArray['MoveInDate'];
                }

                if(isset($personResponseContactArray['BirthDate']) && $personResponseContactArray['BirthDate']!='')
                {
                    $BirthDate =  str_replace('/Date(','',$personResponseContactArray['BirthDate']);
                    $BirthDate =  str_replace(')','',$BirthDate);
                    $BirthDate =  str_replace('/','',$BirthDate);
                    if($this->getTimeZoneFromString($BirthDate)!='')
                    {
                       $BirthDate = substr($BirthDate,0,-5);
                    }
                    $BirthDate = ($BirthDate/1000 );
                    $personResponseContactArray['BirthDate'] = date("Y-m-d", $BirthDate);
                    $BirthDateTemp = date("dmy", $BirthDate);
                    $cprBirthDate  = substr($personCPRNumber,0,6);
                    if($BirthDateTemp!=$cprBirthDate)
                    {
                       $personResponseContactArray['BirthDate'] = date("Y", $BirthDate)."-".substr($personCPRNumber,2,2)."-".substr($personCPRNumber,0,2);
                    }
                    $personCPRData['personBirthDate'] = $personResponseContactArray['BirthDate'];
                }				
                $personCPRData['personCprStatus'] = $personResponseContactArray['CprPersonStatus'];
            }	
            $idqAddressOtherFields 	= $personResponseArray['Addresses'][0];
            $personCPRData['personDistrictName'] = $idqAddressOtherFields['DistrictName'];	
            $personCPRData['personStreetName'] = $idqAddressOtherFields['StreetName'];
            $personCPRData['personStreetNameForAddressingName'] = $idqAddressOtherFields['StreetNameForAddressingName'];

            $personCPRData['personPostCodeIdentifier'] 	= $idqAddressOtherFields['PostCodeIdentifier'];	

            if(isset($personResponseArray['Addresses'][0]['KVHXCode']))
            {
                $personResponseKVHXCodeArray = $personResponseArray['Addresses'][0]['KVHXCode'];				
                $personCPRData['personMunicipalityCode'] = $personResponseKVHXCodeArray['MunicipalityCode'];			
                $personCPRData['personStreetIdentifier'] = $personResponseKVHXCodeArray['StreetBuildingIdentifier'];
                $personCPRData['personFloorIdentifier']  = $personResponseKVHXCodeArray['FloorIdentifier'];
                $personCPRData['personSuiteIdentifier']  = $personResponseKVHXCodeArray['SuiteIdentifier'];
                $personCPRData['personStreetCode'] 		 = $personResponseKVHXCodeArray['StreetCode'];
            }
            $personCPRData['cprCvrNumber'] = $requestPersonCPRNumber;
            $personCPRData['cprStatus']    = 1;
            if(isset($personResponseArray['Addresses'][0]['CompletePostalLabelText']))
                $personCPRData['address'] = $personResponseArray['Addresses'][0]['CompletePostalLabelText'];
            else
                $personCPRData['address'] = '';
        }
        return $personCPRData;
    }
	
    /* 
    Function : get time zone from string
    * @param  dateString string
    * @return string
    * @author shikha
    */
    function getTimeZoneFromString($BirthDateWithTimeZone =''){
        if($BirthDateWithTimeZone=='')
           return '';		
        //Get Time Zone 
        $BirthDateWithTimeZone  = (string)$BirthDateWithTimeZone;
        $skipFirstChar =  substr($BirthDateWithTimeZone,1);
        $pos = strpos($skipFirstChar, '+');
        if ($pos !== false) {
            $string = (string)substr($BirthDateWithTimeZone,$pos+1);
            return $string;
        }	
        $pos = strpos($skipFirstChar, '-');
        if ($pos !== false) {
            $string = (string)substr($BirthDateWithTimeZone,$pos+1);
            return $string;
        }
        return '';
    }
    
    
    public function checkkreditorkodeAction()
    {
        $this->layout('layout/ajaxlayout');
        $request   = $this->getRequest();
        $em = $this->getEntityManager();
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        if($request->isXmlHttpRequest())
        {
            $postData  = $request->getPost();
            
            $entityType = $postData['entityType'];
            $investorLoanType 	=	$postData['investorLoanType'];
            if($entityType =='COMPANY')
            {
                $Kreditorkode    = $postData['KreditorkodeTxt'];
                $companyCVRNumber 	=	trim($postData['KreditorCompanyCVRTxt']);
                $companyType=1;
                $isInvExistId = $em->getRepository('Admin\Entity\Investors')->isCompanyInvestorAlreadyExist($companyCVRNumber,$investorLoanType);
                if(isset($isInvExistId) && $isInvExistId!='')
                {
                    $investorData = $em->getRepository('Admin\Entity\Investors')->getInvestorDataByInvestorId($isInvExistId);
                    $fullname=$investorData['investorName'];
                    $investorCode=$investorData['investorCode'];
                    $result  = array('response'=>"exists", 'message'=>'Kreditor eksisterer allerede','investorCode'=>$investorCode,'investorName'=>$fullname);
                    echo json_encode($result);
                    exit();
                }
                
                $cvrCompanyData = $this->cvrapi($companyCVRNumber);
                if(empty($cvrCompanyData) OR isset($cvrCompanyData['error']))
                {
                    $message = "Kontroller virksomhedens CVR-nummer.";
                    $result  = array('response'=>"incorrectcvr",'message'=>$message);
                    echo json_encode($result);
                    exit();
                }
                
            }
            else if($entityType =='PERSON'){
                $Kreditorkode    = $postData['KreditorkodeCPRTxt'];
                $personCPRNumber = trim($postData['KreditorPersonCPRTxt']);	
                $companyType=0;
               // $investorLoanType 	=	$postData['investorLoanType'];
                $isInvExistId = $em->getRepository('Admin\Entity\Investors')->isPersonInvestorAlreadyExist($personCPRNumber,$investorLoanType);
                if(isset($isInvExistId) && $isInvExistId!='')
                {
                    $investorData = $em->getRepository('Admin\Entity\Investors')->getInvestorDataByInvestorId($isInvExistId);
                    $fullname=$investorData['investorName'];
                    $investorCode=$investorData['investorCode'];;
                    $result  = array('response'=>"exists", 'message'=>'Kreditor eksisterer allerede','investorCode'=>$investorCode,'investorName'=>$fullname);
                    echo json_encode($result);
                    exit();
                }
                
                $apiReceivedData = $this->getPersonDetailsByCprApi($personCPRNumber);
                if($apiReceivedData['cprStatus'] ==0){
                    $message  = "Kontroller personens CPR-nummer.";
                    $result   = array('response'=>"incorrectcpr",'message'=>$message);
                    echo json_encode($result);
                    exit();
                }
            }
            $investorCodeCheck=$em->getRepository('Admin\Entity\Investors')->checkInvestorCode($Kreditorkode,$investorLoanType,$companyType);
            
            if($investorCodeCheck == false) {
                $result  = array('response'=>"success", 'message'=>'Investor code available','investorCode'=>$Kreditorkode); 
            }else {
                $investorCode='';
                $result  = array('response'=>"success", 'message'=>'Investor code not available','investorCode'=>$investorCode); 
            }
        } 
        echo json_encode($result);
        exit();
    }
    /*
    * function to manage company OR Person Kreditor in a case.
    * @param 
    * @return string
    * @author shikha
    */
    public function checkkreditorpersonnewAction()
    {
       $this->layout('layout/ajaxlayout');
        $request   = $this->getRequest(); 
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        if($request->isXmlHttpRequest())
        {
            $postData  = $request->getPost();
            $caseId    = $postData['caseId'];
            $loanId    = $postData['loanId'];	
            $entityType = $postData['entityType'];
            $em = $this->getEntityManager();	
            $formUpdateCounter  = $postData['caseUpdateCounter'];        
            $isCaseUpToDate     = $em->getRepository('Admin\Entity\Cases')->checkIsCaseUpToDate($caseId,$formUpdateCounter);
            if(!$isCaseUpToDate)
            {
                $result =  array('response'=>"multiuserupdate", 'message'=>"stdDisplayMsg.LOAN_MULTIUSER_UPDATE_RESTRICTION_MSG");
            }
            else
            {
                if($entityType =='COMPANY')
                {
                    $companyCVRNumber 	=	trim($postData['KreditorCompanyCVRTxt']);
                    $investorLoanType 	=	$postData['investorLoanType'];
                    
                    
                    // check if the company already added in the Company table.	
                    $isInvExistId = $em->getRepository('Admin\Entity\Investors')->isCompanyInvestorAlreadyExist($companyCVRNumber,$investorLoanType);
                    if(isset($isInvExistId) && $isInvExistId!='')
                    {
                        //Update Company Data
                        $investorData = $em->getRepository('Admin\Entity\Investors')->getInvestorDataByInvestorId($isInvExistId);
                        $fullname=$investorData['investorName'];
                        $investorCode=$investorData['investorCode'];;
                        
						$result  = array('response'=>"exists", 'message'=>'Kreditor eksisterer allerede','investorCode'=>$investorCode,'investorName'=>$fullname);
                    }
                    else
                    { 
                        // Code to check if the CVR number is valid OR not.
                        $cvrCompanyData = $this->cvrapi($companyCVRNumber);
                        $companyType = 1;
                        if(empty($cvrCompanyData) OR isset($cvrCompanyData['error']))
                        {
                            $message = "Kontroller virksomhedens CVR-nummer.";
                            $result  = array('response'=>"incorrectcvr",'message'=>$message);
                        }
                        else {
                            if(isset($cvrCompanyData['name']))
                            {
                                $lastName 	 = '';
                                $fullname 	 = trim($cvrCompanyData['name'], " ");
                                $fullname1=$fullname;
                                $fullname=strtr($fullname, $this->normalizeChars);
                                $fullname=preg_replace('/(?=[^ ]*[^A-Za-z \'-])([^ ]*)(?:\\s+|$)/', '', $fullname);
                                $fullname=trim($fullname);
                                $fullnameArr = explode(" ", $fullname);
                                $firstName 	 = $fullnameArr[0];
                                
                                if(isset($fullnameArr[1]))
                                {
                                    $lastName = $fullnameArr[1];
                                    $firstName = trim($firstName);
                                    $lastName  = trim($lastName);
                                    $investorCode = $firstName[0].$lastName[0];	
                                }
                                else
                                {
                                    $firstName = trim($firstName);
                                    $investorCode = $firstName[0].$firstName[1];						
                                }
                                $investorCodeCheck=$em->getRepository('Admin\Entity\Investors')->checkInvestorCode($investorCode,$investorLoanType,$companyType);
                                if($investorCodeCheck != false) {
                                    $investorCode='';	  
                                }
                            }
                            $result  = array('response'=>"success", 'message'=>'Investor data','investorCode'=>$investorCode,'investorName'=>$fullname1);
                        } 
                    }
                }
                else if($entityType =='PERSON') {
                    $personCPRNumber = trim($postData['KreditorPersonCPRTxt']);	
                    $investorLoanType 	=	$postData['investorLoanType'];
                    $isInvExistId = $em->getRepository('Admin\Entity\Investors')->isPersonInvestorAlreadyExist($personCPRNumber,$investorLoanType);
                    if(isset($isInvExistId) && $isInvExistId!='')
                    {
                        $investorData = $em->getRepository('Admin\Entity\Investors')->getInvestorDataByInvestorId($isInvExistId);
                        $fullname=$investorData['investorName'];
                        $investorCode=$investorData['investorCode'];;
                        $result  = array('response'=>"exists", 'message'=>'Kreditor eksisterer allerede','investorCode'=>$investorCode,'investorName'=>$fullname);
                    }
                    else
                    {
                        $apiReceivedData = $this->getPersonDetailsByCprApi($personCPRNumber);
                        if($apiReceivedData['cprStatus'] ==0){
                            $message  = "Kontroller personens CPR-nummer.";
                            $result   = array('response'=>"incorrectcpr",'message'=>$message);
                        }
                        else
                        {
                            $apiReceivedData['investorLoanType'] = $investorLoanType;
                            $companyType=0;
                            if(isset($apiReceivedData['name']))
                            {
                                $lastName 	 = '';
                                $fullname 	 = trim($apiReceivedData['name'], " ");	
                                $fullname1=$fullname;
                                $fullname=strtr($fullname, $this->normalizeChars);
                                $fullname=preg_replace('/(?=[^ ]*[^A-Za-z \'-])([^ ]*)(?:\\s+|$)/', '', $fullname);
                                $fullname=trim($fullname);
                                $fullnameArr = explode(" ", $fullname);
                                $firstName 	 = $fullnameArr[0];	
                                if(isset($fullnameArr[1]))
                                {
                                    $lastName = $fullnameArr[1];
                                    $firstName = trim($firstName);
                                    $lastName  = trim($lastName);
                                    $investorCode = $firstName[0].$lastName[0];	
                                }
                                else
                                {
                                    $firstName = trim($firstName);
                                    $investorCode = $firstName[0].$firstName[1];						
                                }
                                $investorCodeCheck=$em->getRepository('Admin\Entity\Investors')->checkInvestorCode($investorCode,$investorLoanType,$companyType);
                                if($investorCodeCheck != false) {
                                    $investorCode='';	  
                                }
                            }
                             $result  = array('response'=>"success", 'message'=>'Investor data','investorCode'=>$investorCode,'investorName'=>$fullname1);
                        }
                    }
                }
            }
        }
        echo json_encode($result);
        exit();  
    }    
     
    
    /*
    * function to manage company OR Person Kreditor in a case.
    * @param 
    * @return string
    * @author shikha
    */
    public function checkkreditorpersonAction()
    {	
        $this->layout('layout/ajaxlayout');
        $request   = $this->getRequest(); 
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        if($request->isXmlHttpRequest())
        {
            $postData  = $request->getPost();
            $caseId    = $postData['caseId'];
            $loanId    = $postData['loanId'];	
            $entityType = $postData['entityType'];
            $em = $this->getEntityManager();	
            $formUpdateCounter  = $postData['caseUpdateCounter'];        
            $isCaseUpToDate     = $em->getRepository('Admin\Entity\Cases')->checkIsCaseUpToDate($caseId,$formUpdateCounter);
            if(!$isCaseUpToDate)
            {
                $result =  array('response'=>"multiuserupdate", 'message'=>"stdDisplayMsg.LOAN_MULTIUSER_UPDATE_RESTRICTION_MSG");
            }
            else
            {
                if($entityType =='PERSON')
                {
                    if(strlen($postData['KreditorkodeCPRTxt'])< 2 ) {
                        $result  = array('response'=>"incorrectcvr", 'message'=>'Investor Code Should not be less 2 characters'); 
                    }else {
                    
                    $personCPRNumber = trim($postData['KreditorPersonCPRTxt']);	
                    $investorLoanType 	=	$postData['investorLoanType'];
                    $isInvExistId = $em->getRepository('Admin\Entity\Investors')->isCompanyInvestorAlreadyExist($personCPRNumber,$investorLoanType);
                    if(isset($isInvExistId) && $isInvExistId!='')
                    {
                        $apiReceivedData['investorCode'] = $postData['KreditorkodeCPRTxt'];
                        $addedKreditorId = $em->getRepository('Admin\Entity\Investors')->addOrUpdatePersonAsKridetor($apiReceivedData,$isInvExistId);
                        $message = "Person set as kreditor";
                        $result  = array('response'=>"addedAsKreditor", 'loanId'=>$loanId, 'caseId'=>$caseId, 'message'=>$message,'addedKreditorId'=>$addedKreditorId);
                    }
                    else
                    {
                        $apiReceivedData = $this->getPersonDetailsByCprApi($personCPRNumber);
                        if($apiReceivedData['cprStatus'] ==0){
                            $message  = "Kontroller personens CPR-nummer.";
                            $result   = array('response'=>"incorrectcpr",'message'=>$message);
                        }
                        else
                        {   
                            $apiReceivedData['investorLoanType'] = $investorLoanType;
                            // Code to add the person in Investors table.
                            $apiReceivedData['investorCode'] = $postData['KreditorkodeCPRTxt'];
                            $addedKreditorId = $em->getRepository('Admin\Entity\Investors')->addOrUpdatePersonAsKridetor($apiReceivedData);	
                            $message = "Person added in application and set as kreditor.";
                            $result  = array('response'=>"addedInApplicationAndSetAsKreditor", 'loanId'=>$loanId, 'caseId'=>$caseId, 'message'=>$message,'addedKreditorId'=>$addedKreditorId);	
                        }
                        
                        $investorName = $em->getRepository('Admin\Entity\Investors')->getInvestorNameByInvestorId($addedKreditorId);
                        $result['option'] = "<option value='".$addedKreditorId."'>".$investorName."</option>";
                    }
                }
                }
                else if($entityType =='COMPANY')
                {
                    
                    if(strlen($postData['KreditorkodeTxt'])< 2 ) {
                        $result  = array('response'=>"incorrectcvr", 'message'=>'Investor Code Should not be less 2 characters'); 
                    }else {
                    $companyCVRNumber 	=	trim($postData['KreditorCompanyCVRTxt']);
                    $investorLoanType 	=	$postData['investorLoanType'];
                    $isInvExistId = $em->getRepository('Admin\Entity\Investors')->isCompanyInvestorAlreadyExist($companyCVRNumber,$investorLoanType);
                    if(isset($isInvExistId) && $isInvExistId!='')
                    {
                        $cvrCompanyData['investorCode'] = $postData['KreditorkodeTxt'];
                        //Update Company Data
                        $existCompanyId	= $em->getRepository('Admin\Entity\Investors')->addOrUpdateCompanyAsKridetor($cvrCompanyData,$isInvExistId);
                        $message = "Company set as kreditor";
                        $result  = array('response'=>"addedAsKreditor", 'loanId'=>$loanId, 'caseId'=>$caseId, 'message'=>$message,'addedKreditorId'=>$existCompanyId);
                    }
                    else
                    {   
                        $cvrCompanyData = $this->cvrapi($companyCVRNumber);
                        if(empty($cvrCompanyData) OR isset($cvrCompanyData['error']))
                        {
                            $message = "Kontroller virksomhedens CVR-nummer.";
                            $result  = array('response'=>"incorrectcvr",'message'=>$message);
                        }
                        else
                        {   
                            $cvrCompanyData['investorLoanType'] = $investorLoanType;// code to add company data in company table.
                            $cvrCompanyData['investorCode'] = $postData['KreditorkodeTxt'];
                            $existCompanyId = $em->getRepository('Admin\Entity\Investors')->addOrUpdateCompanyAsKridetor($cvrCompanyData);	
                            $message = "Company added in application and set as kreditor.";
                            $result  = array('response'=>"addedInApplicationAndSetAsKreditor", 'loanId'=>$loanId,'caseId'=>$caseId, 'message'=>$message,'addedKreditorId'=>$existCompanyId);
                        }
                        $investorName = $em->getRepository('Admin\Entity\Investors')->getInvestorNameByInvestorId($existCompanyId);
                        $result['option'] = "<option value='".$existCompanyId."'>".$investorName."</option>";
                    }
                }
                }
                else{
                    $result =   array('response'=>"fail", 'message'=>"There are some internal errors. Please contact administrator" );		
                }
            }
        }
        echo json_encode($result);
        exit();    
    }
	
     /*
    * Funtion to update default new loans value 
    * @param n/a
    * @return json array
    * @author shikha    */	
    public function updateDefaultNewLoanValueAction()
    {
        $this->layout('layout/ajaxlayout');
        $request    = $this->getRequest(); 
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        //Get agent details from session
        $agent_session 	= new Container('Uobmember');		
        $username = $agent_session->userName;
        $userId = $agent_session->userId;
        if(!isset($userId))
        {
            $result = array('status' => 'error', 'message' => 'Agent session destroy');
        } 
        if($request->isXmlHttpRequest() && isset($userId))
        { 
            $em = $this->getEntityManager();
            $postData   = $request->getPost();            
            $caseId     = $postData['caseId'];
            $loanId     = $postData['loanId'];           
            $formUpdateCounter  = $postData['caseUpdateCounter'];        
            $isCaseUpToDate     = $em->getRepository('Admin\Entity\Cases')->checkIsCaseUpToDate($caseId,$formUpdateCounter);
            if(!$isCaseUpToDate)
            {
                $result =  array('response'=>"multiuserupdate", 'message'=>"stdDisplayMsg.LOAN_MULTIUSER_UPDATE_RESTRICTION_MSG");
            }
            else 
            {  
                unset($postData['caseId']);
                unset($postData['loanId']);
                unset($postData['caseUpdateCounter']);                
                $em->getRepository('Admin\Entity\Loans')->updateLoansDataByloanId($postData,$loanId);
                $result = array('response'=>"success", 'message'=>"stdDisplayMsg.LOAN_RECORD_UPDATE_MSG");	               
            }                           
        }
        echo json_encode($result);
        exit();
    }
    
     /*
    * Funtion to update loanFinancingNeed value
    * @param n/a
    * @return json array
    * @author shikha
    */	
    public function updateLoanFinancingNeedValueAction()
    {
        $this->layout('layout/ajaxlayout');
        $request    = $this->getRequest(); 
        $result = array('status' => 'error', 'message' => 'There was some error. Try again.');
        //Get agent details from session
        $agent_session 	= new Container('Uobmember');		
        $username = $agent_session->userName;
        $userId = $agent_session->userId;
        if(!isset($userId))
        {
            $result = array('status' => 'error', 'message' => 'Agent session destroy');
        } 
        if($request->isXmlHttpRequest() && isset($userId))
        { 
            $em = $this->getEntityManager();
            $postData   = $request->getPost(); 
            $loanId     = $postData['loanId'];           
            $formUpdateCounter  = $postData['caseUpdateCounter'];        
            $isCaseUpToDate     = $em->getRepository('Admin\Entity\Cases')->checkIsCaseUpToDate($caseId,$formUpdateCounter);
            if(!$isCaseUpToDate)
            {
                $result =  array('response'=>"multiuserupdate", 'message'=>"stdDisplayMsg.LOAN_MULTIUSER_UPDATE_RESTRICTION_MSG");
            }
            else 
            {
                unset($postData['loanId']);
                unset($postData['caseUpdateCounter']);                
                $em->getRepository('Admin\Entity\Loans')->updateLoansDataByloanId($postData,$loanId);
                $result = array('response'=>"success", 'message'=>"loanFinancingNeed value update successfully.");	               
            }                           
        }
        echo json_encode($result);
        exit();
    }
    
       /*
    * Function: Create document from model window .This function is used for 3 type documents.
    * @param N/A
    * @return json array
    * @author shikha
    */
    public function deleteLoansInvoiceFromEconomic($economicId)
    {   
        $em = $this->getEntityManager();
        $fileterFiledName ='notes.textLine1';
        $economicAPIURL = trim(ECONOMIC_API_URL,"/");
        $EURL =$economicAPIURL.'/invoices/drafts/'.$economicId;
        $economicSettingDetails = $em->getRepository('Admin\Entity\EconomicSetting')->getEconomicDefaultValue();
        $ch = curl_init($EURL);   
        $request_headers = array();
        $request_headers[] = 'X-AppSecretToken: '. $economicSettingDetails['appSecretToken'];;
        $request_headers[] = 'X-AgreementGrantToken: '. $economicSettingDetails['agreementGrantToken'];
        $request_headers[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");                                                                     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                            
        $result = curl_exec($ch); 
        $consentResponseArray = json_decode($result, true);
        if(isset($consentResponseArray['deletedCount']) && $consentResponseArray['deletedCount'] == 1 ){ return true;}else{ return false;}        
    }	
    
    /*
    * Function: this function fist fetch the economic data(bookedInvoiceNumber) from E-conomic site by uniqueId, Then delete the document from E-conomic, This function is used for 3 type documents. 
    * @param N/A
    * @return json array
    * @author shikha
    */
    public function deleteLoansBookedInvoiceFromEconomic($economicId,$uniqueId)
    {  
        $fileterFiledName ='notes.textLine1';
        $em = $this->getEntityManager();
        // Code start for fetting the bookedInvoiceNumber by unique ID
        $economicAPIURL = trim(ECONOMIC_API_URL,"/");
        $EURL =$economicAPIURL.'/invoices/booked?filter='.$fileterFiledName.'$eq:'.urlencode($uniqueId);
        $economicSettingDetails = $em->getRepository('Admin\Entity\EconomicSetting')->getEconomicDefaultValue();
        $ch = curl_init($EURL);   
        $request_headers = array();
        $request_headers[] = 'X-AppSecretToken: '. $economicSettingDetails['appSecretToken'];;
        $request_headers[] = 'X-AgreementGrantToken: '. $economicSettingDetails['agreementGrantToken'];
        $request_headers[] = 'application/json; charset=UTF-8';        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                            
        $result = curl_exec($ch); 
        $invoiceEconomicData= array();
        $invoiceEconomicArr = json_decode($result, true);
        $invoiceEconomicData =$invoiceEconomicArr['collection'][0];
        
        $bookedInvoiceNumber = $invoiceEconomicData['bookedInvoiceNumber'];       
        $EURL =$economicAPIURL.'/invoices/booked/'.$bookedInvoiceNumber;
        $ch = curl_init($EURL);   
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                            
        $resultBookedInvoice = curl_exec($ch); 
        $dataToCreateDraftInvoiceArr = json_decode($resultBookedInvoice, true);  
        $dataToCreateDraftInvoice = $dataToCreateDraftInvoiceArr;
      
        $lineRowCounter=0;
        if(isset($dataToCreateDraftInvoice['lines']) && count($dataToCreateDraftInvoice['lines'])>=1){
            foreach($dataToCreateDraftInvoice['lines']  as $lineRowObject){
               $negativeQuantity = $lineRowObject['quantity']; 
               $lineRowObject['quantity'] = -$negativeQuantity;
               $dataToCreateDraftInvoice['lines'][$lineRowCounter]=$lineRowObject;
               $lineRowCounter++;
            }
        }         
        // code start for creating a draft invoice..
        $economicAPIURL = trim(ECONOMIC_API_URL,"/");
        // Updated on 10 August
        $POST_URL =$economicAPIURL.'/invoices/drafts';
        $ch1 = curl_init($POST_URL); 
        $request_headers_add = array();
        $request_headers_add[] = 'X-AppSecretToken: '. $economicSettingDetails['appSecretToken'];;
        $request_headers_add[] = 'X-AgreementGrantToken: '. $economicSettingDetails['agreementGrantToken'];
        $request_headers_add[] = 'Accept: application/json';
        $request_headers_add[] = 'Content-Type: application/json';        
          if(!isset($dataToCreateDraftInvoice['recipient']['vatZone'])){
                $dataToCreateDraftInvoice['recipient']['vatZone']['name']= "Domestic";
                $dataToCreateDraftInvoice['recipient']['vatZone']['vatZoneNumber']=1;
                $dataToCreateDraftInvoice['recipient']['vatZone']['enabledForCustomer']=true;
                $dataToCreateDraftInvoice['recipient']['vatZone']['enabledForSupplier']=true;          
             }
           $dataToCreateDraftInvoice['notes']['textLine1'] =  $dataToCreateDraftInvoice['notes']['textLine1'].'-B';    
        $economicInvoiceDataObject = json_encode($dataToCreateDraftInvoice);
        curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $request_headers_add);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $economicInvoiceDataObject);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);                                                            
        $resultPostInvoice = curl_exec($ch1); 
        curl_close($ch1);
        $consentResponseArrayInvoice = json_decode($resultPostInvoice, true);     
        if(isset($consentResponseArrayInvoice['draftInvoiceNumber']) && $consentResponseArrayInvoice['draftInvoiceNumber']!=''){
            $bookDraftInvoiceData['draftInvoice']['draftInvoiceNumber']= $consentResponseArrayInvoice['draftInvoiceNumber'];
                $bookInvoiceUrl = $economicAPIURL.'/invoices/booked';
                $chBook = curl_init($bookInvoiceUrl);                 
                $request_headers_book = array();
                $request_headers_book[] = 'X-AppSecretToken: '. $economicSettingDetails['appSecretToken'];;
                $request_headers_book[] = 'X-AgreementGrantToken: '. $economicSettingDetails['agreementGrantToken'];
                $request_headers_book[] = 'Accept: application/json';
                $request_headers_book[] = 'Content-Type: application/json';        
          
                $bookDraftInvoiceDataObject = json_encode($bookDraftInvoiceData);
                curl_setopt($chBook, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
                curl_setopt($chBook, CURLOPT_RETURNTRANSFER, true); 
                curl_setopt($chBook, CURLOPT_HTTPHEADER, $request_headers_book);
                curl_setopt($chBook, CURLOPT_POSTFIELDS, $bookDraftInvoiceDataObject);
               // curl_setopt($chBook, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                curl_setopt($chBook, CURLOPT_SSL_VERIFYPEER, false);                                                            
                $resultBookedInvoice = curl_exec($chBook); 
                curl_close($chBook);
                $resultBookedInvoiceArray = json_decode($resultBookedInvoice, true);    
             if(isset($resultBookedInvoiceArray['bookedInvoiceNumber'])){
                 return true;                 
             }else{
                   return false;        
             }            
        }else{
            return false;            
        }          
    }
    
    public function wipeOutPastCasesAction()
    {  
       $em = $this->getEntityManager(); 
       $cases     = $em->getRepository('Admin\Entity\Cases')->completedCasesToWipeout();
       $cases     = $em->getRepository('Admin\Entity\Cases')->expiredCasesToWipeout();
    }
    
    public function deleteExistingCaseAmountAction()
    {  
       $em = $this->getEntityManager(); 
       $cases     = $em->getRepository('Admin\Entity\Cases')->getExsitingCases();
       
       foreach($cases as $case) {
           
           $em->getRepository('Admin\Entity\Loans')->removeExsitingCasesAmount($case['id']);
           echo "case id :- ".$case['id']." updated <br>";
       }
        echo " All Existing cases amount for Stilles garanti, Udfærdiges skøde and Tinglyses skøde Removed";die;
    }
    public function updateCreditValuesByCaseIdAction(){
        $em = $this->getEntityManager(); 
        $cases     = $em->getRepository('Admin\Entity\Cases')->getExsitingCasesToUpdateCredit();
        foreach($cases as $case) {
            
        }
    }
}