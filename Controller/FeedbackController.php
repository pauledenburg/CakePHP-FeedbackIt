<?php
class FeedbackController extends AppController {
	
	public $uses = array('FeedbackIt.Feedbackstore');

	public function beforeFilter(){
		parent::beforeFilter();

		//Config file location (if you use it)
		$configfile = APP.'Plugin'.DS.'FeedbackIt'.DS.'Config'.DS.'feedbackit-config.php';

		//Check if a config file exists:
		if( file_exists($configfile) AND is_readable($configfile) ){
			//Load config file into CakePHP config 
			Configure::load('FeedbackIt.feedbackit-config');

			return true;
		}

		//Throw error, config file required
		throw new NotFoundException( __('No config file found. Please create one: ').' ('.$configfile.')' );
	}

	/*
	Ajax function to save the feedback form. Lots of TODO's on this side.
	 */
	public function savefeedback(){

		//Is ajax action
		$this->layout='ajax';

		//Do not autorender
		$this->autoRender = false;

		//Save screenshot:
		$this->request->data['screenshot'] = str_replace('data:image/png;base64,', '', $this->request->data['screenshot']);

		//Add current time to data
		$this->request->data['time'] = time();

		//Check name
		if( empty($this->request->data['name']) ){
			$this->request->data['name'] = "Anonymous";
		}
		
		//Create feedbackObject
		$feedbackObject = $this->request->data;
		
		//Determine method of saving
		if( $method = Configure::read('FeedbackIt.method') ){

			//Check method exists in Model
			if( ! (method_exists($this->Feedbackstore, $method)) ){
				throw new NotImplementedException( __('Method not found in Feedbackstore model:').' '.$method );
			}

			//Use method to save:
			if( $this->Feedbackstore->$method($feedbackObject) ){
				die("Feedback saved");
			}else{
				$this->response->statusCode(500);
				die("Error saving feedback");
			}
		}

		//Throw error, method required
		throw new NotFoundException( __('No save method found in config file') );
	}

	/*
	Example index function for current save in tmp dir solution
	 */
	public function index(){

		if(Configure::read('FeedbackIt.method') != 'filesystem'){
			$this->Session->setFlash(__('This function is only available with filesystem save method'));
			$this->redirect($this->referrer());
		}

		//Find all files in feedbackit dir
		$savepath = Configure::read('FeedbackIt.methods.filesystem.location');

		//Check dir
		if( ! file_exists($savepath) ){
			throw new NotFoundException( __('Feedback location not found: ').$savepath );
		}

		//Creat feedback array in a cake-like way
		$feedbacks = array();

		//Loop through files
		foreach(glob($savepath.'*.feedback') as $feedbackfile){

			$feedbackobject = unserialize(file_get_contents($feedbackfile));
			$feedbacks[$feedbackobject['time']]['Feedback'] = $feedbackobject;

		}

		//Sort by time
		krsort($feedbacks);

		$this->set('feedbacks',$feedbacks);
	}

	/*
	Temp function to view captured image from index page
	 */
	public function viewimage($feedbackfile){

		$savepath = Configure::read('FeedbackIt.methods.filesystem.location');

		if( ! file_exists($savepath.$feedbackfile) ){
			 throw new NotFoundException( __('Could not find that file') );
		}

		$feedbackobject = unserialize(file_get_contents($savepath.$feedbackfile));

		if( ! isset($feedbackobject['screenshot']) ){
			throw new NotFoundException( __('No screenshot found') );
		}

		$this->set('screenshot',$feedbackobject['screenshot']);

		$this->layout = 'ajax';
	}
}
?> 
