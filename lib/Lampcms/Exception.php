<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


/**
 * All Exceptions defined here
 * @important Allways include this file!
 * 
 * @author Dmitri Snytkine
 *
 */
namespace Lampcms;

/**
 * Our main Exception class
 * this makes it possible to make exceptions
 * messages translatable via our Translation class
 *
 * @author Dmitri Snytkine
 *
 */
class Exception extends \Exception
{

	/**
	 * Array of sprintf replacement arguments
	 *
	 * @var array
	 */

	private $aArgs = array();

	/**
	 * Extra flag
	 * this flag is used by the catch(){} block
	 * when deciding in what format
	 * to send json array.
	 * if set to true, the json array will be enclosed
	 * in javascript tag and the header will be
	 * text/html
	 *
	 * @var boolean
	 */

	protected $bHtml = false;

	public function __construct($sMessage = null, array $aArgs = null, $intCode = 0,
	$bHTML = false)
	{
		parent::__construct($sMessage, $intCode);
		$this->aArgs = $aArgs;
		$this->bHTML = $bHTML;
	}

	/**
	 * Returns an extra argument (array) that was passed
	 * to this exception class
	 *
	 * @return array
	 */
	public function getArgs()
	{
		return $this->aArgs;
	}


	/**
	 * Return this->bHtml
	 *
	 * @return unknown
	 */
	public function getHtmlFlag()
	{
		return $this->bHtml;
	}


	/**
	 * Try to translate the error message
	 * and then if the request is Ajax request, sends
	 * out the json message containing error, otherwise
	 * formats the error string, depending of debug
	 * option, will include extra backtrace of exception
	 *
	 *
	 * @param object $e Exception
	 *
	 * @param string $sMessage [optional]
	 *
	 * @return string formatted error message made
	 *
	 * from data in Exception object
	 */
	public static function formatException(\Exception $e, $sMessage = '')
	{
		$sMessage = (!empty($sMessage)) ? $sMessage : $e->getMessage();
		$sMessage = $e->getMessage();
		$bHtml = ($e instanceof \Lampcms\Exception) ? $e->getHtmlFlag() : false;

		//$oTr = clsTr::getInstance(null, 'exceptions');
		//$lang = (isset($_SESSION) && !empty($_SESSION['lang'])) ? $_SESSION['lang'] : 'en';
		//$oTr->setLang($lang);
		if ($e instanceof Lampcms\DevException) {
			$sMessage = ( (defined('LAMPCMS_DEBUG')) && true === LAMPCMS_DEBUG) ? $e->getMessage() : 'Error occured';//$oTr->get('generic_error', 'exceptions');
		}

		//$sMessage = $oTr->get($sMessage, 'exceptions');

		$aArgs = ($e instanceof \Lampcms\Exception) ? $e->getArgs() : null;
		$sMessage = (!empty($aArgs)) ? vsprintf($sMessage, $aArgs) : $sMessage;

		$sError = '';
		$sTrace = $e->getTraceAsString();
		$strFile = $e->getFile();
		$intLine = $e->getLine();
		$intCode = ($e instanceof \ErrorException) ? $e->getSeverity() : $e->getCode();

		$strLogMessage = 'LampcmsError exception caught: '.$sMessage."\n".'error code: '.$intCode."\n".'file: '.$strFile."\n".'line: '.$intLine."\n".'stack: '.$sTrace."\n";
		d('vars: '.print_r(Request::getInstance()->toArray(), true)."\n".$strLogMessage);

		$sError .= $sMessage."\n";

		if ( (defined('LAMPCMS_DEBUG')) && true === LAMPCMS_DEBUG) {
			$sError .= 'error code: '.$intCode."\n";
			$sError .= 'file: '.$strFile."\n";
			$sError .= 'line: '.$intLine."\n";

			if (!empty($sTrace)) {
				$sError .= 'trace: '. $sTrace."\n";
			}
		}

		d('cp');

		/**
		 * If this exception has E_USER_WARNING error code
		 * then it was thrown when parsing some ajax-based
		 * request.
		 *
		 * We then need to only send json array with
		 * only one key 'exception'
		 */
		if ( (E_USER_NOTICE === $e->getCode()) || Request::isAjax()) {

			d('will be sending out exception as ajax');
			
			
			/**
			 * if this exception was thrown when uploading
			 * file to iframe, then we need to add 'true' as
			 * the last (2nd arg) to fnSendJson
			 */
			$a = array(
			'exception'=>$sError, 
			'errHeader' => 'Error',/*$oTr->get('errHeader', 'qf'),*/
			'type' => get_class($e));

			if($e instanceof Lampcms\FormException){
				$a['fields'] = $e->getFormFields();
			}
			d('json array of exception: '.print_r($a, 1));

			Responder::sendJSON($a);
		}

		d('$sError: '.$sError);

		return $sError;
	}

} // end Lampcms\Exceptions class

/**
 * this exception is thrown when it is determined
 * that user has not yet added an email address
 * to the account.
 * 
 * @author Dmitri Snytkine
 *
 */
class NoemailException extends \Lampcms\Exception{}

/**
 * This exception should be thrown when
 * only developer should see the actual error message,
 * a regular user will see a generic error message.
 *
 * A good canditate for throwing this exception is from the
 * class constructors when we check that a valid data or objects
 * are passed to constructor.
 * A regular user does not heed to see these technical messages
 * and instead will see a generic error message.
 *
 * LampcmsDevException allways sets the error code to E_ERROR, so
 * adding the intCode as a third argument is irrelevant and will not make any difference.
 * Why is the third argument even there then? It's for convenience so that
 * a developer can change the new LampcmsException to new LampcmsDevException
 * anywhere in the code at any time without having to re-arrange arguments.
 *
 * Remember that any exception causes an email to be sent to admin, but
 * LampcmsDevException will also set 'Priority: 1' header
 *
 * If you want the LampcmsException or regular Exception (not recommended to just use regular Exception)
 * to cause emails be sent with high priority, you must set the intCode to E_ERROR or E_USER_ERROR
 * or PEAR_LOG_ALERT
 *
 */
class DevException extends Exception
{
	public function __construct($message = null, array $aArgs = null, $intCode = 0, $boolHTML = false)
	{
		/**
		 * sets the error code to E_ERROR, which will cause the Log observer
		 * to set high priority in the emails sent to admins/developers
		 */
		parent::__construct($message, $aArgs, E_ERROR, $boolHTML);
	}
}


class IniException extends DevException{}


class DBException extends DevException
{
	protected $sqlState;

	public function __construct($message, $mysqlErrCode = 0, $sqlState = 0)
	{
		parent::__construct($message, null, $mysqlErrCode);
		$this->sqlState = $sqlState;
	}
}

/**
 * Class represents an exception
 * that is throws from insert or update statements
 * due to uniqueness index violation
 *
 * @author Dmitri Snytkine
 *
 */
class PDODuplicateException extends DBException
{
	/**
	 * This will be the value that caused the duplicate
	 * constraints violation
	 * But it may also be an empty string in case
	 * the preg_match fails to extract it. This can happened if
	 * mysql changes the format of error message in some
	 * future releases
	 * @var string
	 */
	protected $dupValue = '';

	/**
	 * Numeric index key
	 * of index in which the uniquness violation occured
	 * @var int
	 */
	protected $dupKey;

	public function __construct($message, $dupValue, $key)
	{
		parent::__construct($message, 1062, 23000);
		$this->dupValue = $dupValue;
		$this->dupKey = $key;

	}

	public function getDupValue()
	{
		return $this->dupValue;
	}

	protected function getDupKey()
	{
		return $this->dupKey;
	}
}

//class PagerException extends Exception{}
/**
 * Custom exception class
 * used in admin classes
 *
 */
class AdminException extends Exception{}

class Lampcms404Exception extends Exception{}

class CookieAuthException extends Exception{}

class AclException extends Exception{}

class AclRoleRegistryException extends AclException{}

/**
 * This exception indicates that user does
 * not have privilege to access
 * the resource or to perform an action
 *
 * @author Dmitri Snytkine
 *
 */
class AccessException extends Exception{}

/**
 * 
 * Exception indicates that user is
 * not logged in but the action is only
 * available to logged in users
 * @author Dmitri Snytkine
 *
 */
class MustLoginException extends Exception{}

/**
 * This exception indicated that
 * user must login in order
 * to view or use the page
 * The HTTP code
 * @author Dmitri Snytkine
 *
 */
class AuthException extends Exception{}

/**
 * 
 * This means user has not confirmed
 * email address
 *
 */
class UnactivatedException extends Exception{}

/**
 * Special exception indicates
 * that page can be found at different url
 *
 * @author Dmitri Snytkine
 *
 */
class RedirectException extends Exception
{
	/**
	 * Constructor
	 *
	 * @param string $strMessage
	 * @param int $intHttpCode HTTP response code
	 * @param str $strNewLocation must be a full url where
	 * the page can be found
	 */
	public function __construct($strNewLocation, $intHttpCode = 301, $boolHTML = true)
	{
		parent::__construct($strNewLocation, null, $intHttpCode, $boolHTML);
	}
}

/**
 * Special exception for Form Field validations
 * it has extra param sFormField
 * which can be used when setting form error
 * so that in addition to the error message
 * we know which form field(s) caused the
 * form validation error
 *
 * @author Dmitri Snytkine
 *
 */
class FormException extends DevException{

	protected $aFields = null;

	/**
	 * Constructor
	 * @param string $sMessage error message
	 * @param array $aFormFields regular array with names of form fields
	 * that caused validation error
	 *
	 * @param array $aArgs additional optional array of args for
	 * vsprintf. This is in case we need to translate the error message
	 * and then apply the replacement vars.
	 */
	public function __construct($sMessage, $formFields = null, array $aArgs = null)
	{
		parent::__construct($sMessage, $aArgs);

		if(is_string($formFields)){
			$formFields = array($formFields);
		}

		$this->aFields = $formFields;
	}

	public function getFormFields()
	{
		return $this->aFields;
	}
}

class FacebookApiException extends Exception{}

class ImageException extends DevException{}

class ExternalAuthException extends Exception{}

class GFCAuthException extends ExternalAuthException{}

class TwitterAuthException extends ExternalAuthException{}

class FacebookAuthException extends ExternalAuthException{}

class CaptchaLimitException extends Exception{}

class TokenException extends Exception{}

class TrException extends Exception{}

class TwitterException extends Exception{}

class LoginException extends Exception{}

class WrongUserException extends LoginException{}

class WrongPasswordException extends LoginException{}

class MultiLoginException extends LoginException{}

/**
 * Base Exception class for the clsHttp class
 * used to extend HttpException
 * @author Dmitri Snytkine <cms@lampcms.com>
 *
 */
class HttpResponseErrorException extends Exception{}

/**
 * Exception class that represents
 * the response
 * Exception of this class or sub-class
 * should be used when a response code
 * other than 200 is received from the http server
 *
 * @author Dmitri Snytkine <cms@lampcms.com>
 *
 */
class HttpResponseCodeException extends HttpResponseErrorException
{

	/**
	 * The response code
	 * the http server returnes
	 * This is usually one of these:
	 * 301, 302, 303, 307
	 * of these 301 means permanent redirect,
	 * others mean temporary
	 *
	 * @var int
	 */
	protected $httpResponseCode;

	/**
	 * Constructor
	 *
	 * @param Exception $message
	 * @param int $httpCode http response code as received from http server
	 * @param int $code arbitrary code, usually used to indicate the level of error
	 * @param object $prevException object of type Exception containing the
	 * previous (inner) Exception
	 *
	 * @return void
	 */
	public function __construct($message, $httpCode, $code = 0, Exception $prevException = null){

		/**
		 * The parent class is HttpException
		 * which does NOT accept the third param,
		 * so we must only pass $message and $code here!
		 */
		parent::__construct($message, $code);

		$this->httpResponseCode = $httpCode;
	}

	/**
	 * Getter for the httpResponseCode variable
	 *
	 * @return int value of $this->httpResponseCode
	 */
	public function getHttpCode()
	{
		return $this->httpResponseCode;
	}
}

/**
 * Exception class the represents the
 * http server timeout condition
 *
 * @author Dmitri Snytkine <cms@lampcms.com>
 *
 */
class HttpTimeoutException extends HttpResponseErrorException{}
class Http304Exception extends HttpResponseErrorException{}
class Http401Exception extends HttpResponseErrorException{}
class Http404Exception extends HttpResponseErrorException{}
class Http400Exception extends HttpResponseCodeException{}
class Http500Exception extends HttpResponseCodeException{}

/**
 * Exception class
 * that is thrown in case the http server
 *  returned the 200 code (OK) but
 *  the body of response is totally empty
 *
 * @author Dmitri Snytkine <cms@lampcms.com>
 *
 */
class HttpEmptyBodyException extends HttpResponseErrorException{}

/**
 * Exception class that represents the
 * Redirect error message received from the http server
 *
 * @author Dmitri Snytkine <cms@lampcms.com>
 *
 */
class HttpRedirectException extends HttpResponseCodeException
{
	/**
	 * value of the new uri where the redirect leads
	 * This is the value of the Location: header
	 *
	 * @var string
	 */
	protected $newURI;


	public function __construct($newLocation, $httpCode, $code = 0, Exception $prevException = null){
		$m = 'URI changed to '.$newLocation;

		parent::__construct($m, $httpCode, $code, $prevException);

		$this->newURI = $newLocation;

	}

	/**
	 * Getter for $newURI member variable
	 *
	 * @return string value of $this->newURI
	 * This is the value of the new URI sent as a
	 * result of redirect http message
	 */
	public function getNewURI()
	{
		return $this->newURI;
	}

}

/**
 * Thrown by clsQuestionparser in case question could not be
 * added for some reason.
 *
 * Question filter can also throw this exception or
 * can cause this exception by throwing FilterException
 *
 *
 * @author Dmitri Snytkine
 *
 */
class QuestionParserException extends Exception{}

/**
 * Thrown by clsAnswerParser in case question could not be
 * added for some reason, for example in case of detecting
 * a duplicate answer
 *
 * Answer filter can also throw this exception or
 * can cause this exception by throwing FilterException
 *
 *
 * @author Dmitri Snytkine
 *
 */
class AnswerParserException extends Exception{}

/**
 * Base exception thrown by varios post filters like question filter,
 * comment filter, answer filter, etc...
 *
 * Each filter should extend this class.
 *
 * @author Dmitri Snytkine
 *
 */
class FilterException extends Exception {}


class HTML2TextException extends Exception{}

?>