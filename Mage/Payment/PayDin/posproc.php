<?php
require_once 'app/Mage.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

require Mage::getBaseDir() . '/app/code/community/Diners/DNmod/Controller/TripleDESEncryption.php';

# Diners
$_3des = new TripleDESEncryption();
# Log
Mage::log("posproc	xmlreq	{$_REQUEST['xmlReq']}", null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));

# Obtener [_POST]
if( $postdata = $_REQUEST['xmlReq'] )
{
	# Decodificar [_POST]
	$postdata = urldecode($postdata); 

	$querystring = $_3des->decrypt($postdata, Mage::getStoreConfig('payment/dnmod_shared/simetricKey'), Mage::getStoreConfig('payment/dnmod_shared/vectorIni'));
	# Extraer Tramas
	$tipo = true;
	parse_str($querystring);
	# Validar si estan correctas el querystring
	if($tipo === true){
		Mage::log("posproc	xmlreq	--NOTVALID-- {$_REQUEST['xmlReq']}", null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));
		echo 'ESTADO=KO';
		exit;
	}
	## LOG
	try {
		/**
		 * Create and write with append mode
		 */
		Mage::log("posproc	decode	" . urldecode($_REQUEST['xmlReq']), null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));
		Mage::log("posproc	querystring	" . $querystring, null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));
		
		$_data_dnmod = array(
			'api_refer'		=>	$datos,
			'api_nauto'		=>	$aut,
			'api_tcre'		=>	$Cre,
			'api_mplazo'	=>	$mes,
			'api_ttar'		=>	$ttar,
			'api_sub'		=>	($sub/100),
			'api_iva'		=>	($Iva/100),
			'api_ice'		=>	($Ice/100),
			'api_int'		=>	($Int/100),
			'api_tot'		=>	($Tot/100),
			'api_orderid'	=>	$tNo,
			'api_stat'		=>	$tipo,
		);
		
		$_flag_dnmod = true;
		$_validator_api = Mage::getModel('dnmod/api_base')->getCollection()
			->addFilter('api_orderid', $tNo)
			->getData();
		if($_validator_api == null) $_flag_dnmod = false;
		
		if($_flag_dnmod){
			#$fw->appendLine( date("Ymd His") . "	posproc	find	TRUE");
			$updateId = $_validator_api[0]['api_id'];
			
			$_api_base = Mage::getModel('dnmod/api_base')
							->load($updateId)
							->addData($_data_dnmod);
			$_issaved = $_api_base->setId($updateId)
							->save();
		}else{
			#$fw->appendLine( date("Ymd His") . "	posproc	find	FALSE");
		}
		# Model Sales
		$order = Mage::getModel('sales/order')->loadByIncrementId($tNo);
		# Return OK/KO
		#var_dump($order);
		
		if ($tipo == 'P' && $_flag_dnmod) 
		{
			Mage::log("posproc	response	ESTADO=OK", null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));
			
			$f_passed_status = Mage::getStoreConfig('payment/dnmod_shared/success_status');
			
			$order->setState($f_passed_status, $f_passed_status, Mage::helper('dnmod')->__('The payment is AUTHORIZED by Diners.'),true);
			$order->setVisibleOnFront(1);
			$order->sendOrderUpdateEmail(true, Mage::helper('dnmod')->__('Your payment is authorized.'));
			
			echo 'ESTADO=OK'; 
		}
		else
		{
			Mage::log("posproc	response	ESTADO=KO", null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));
			
			$f_wait_status = Mage::getStoreConfig('payment/dnmod_shared/failure_status');
			
			$order->setState($f_wait_status, $f_wait_status, Mage::helper('dnmod')->__('The payment is REVERSED by Diners.'),true);
			$order->setVisibleOnFront(1);
			$order->sendOrderUpdateEmail(true, Mage::helper('dnmod')->__('Your payment is reversed.'));
			
			echo 'ESTADO=KO';
		}
	} catch (Exception $e){
		Mage::helper('dnmod')->__('Fatal Error: ' .$e->getMessage());
	} catch (FileException $fe) {
		echo $fe;
	} catch (FileNotFoundException $fnfe) {
		echo $fnfe;
	} catch (IOException $e) {
		echo $e;
	}
}
else
{
	try {
		Mage::log("posproc	xmlreq	BAD REQUEST", null, Mage::getStoreConfig('payment/dnmod_shared/logfilename'));
		echo 'ESTADO=KO';
	} catch (Exception $e){
		Mage::helper('dnmod')->__('Fatal Error: ' .$e->getMessage());
	} catch (FileException $fe) {
		echo $fe;
	} catch (FileNotFoundException $fnfe) {
		echo $fnfe;
	} catch (IOException $e) {
		echo $e;
	}
}
