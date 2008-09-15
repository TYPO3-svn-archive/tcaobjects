<?php

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';

require_once PATH_t3lib . 'class.t3lib_basicfilefunc.php';

class tx_tcaobjects_quickform extends HTML_QuickForm {

	protected $formname;

	protected $prefix;

	/**
	 * @var tx_tcaobjects_object
	 */
	protected $object;

	protected $formDefinition;

	/**
	 * @var HTML_QuickForm_Renderer	Quickform renderer
	 */
	protected $renderer;

	protected $onValidated;

	protected $onNotValidated;

	protected $onCancel;

	protected $cancelButton;

	public static $ignoreTokens = false;




	/**
	 * Constructor
	 *
	 * @param 	string	prefix
	 * @param 	string	formName
	 * @param 	string	(optional) method ("post" or "get")
	 * @param 	string	(optional) action
	 * @param 	string	(optional) target
	 * @param 	array	(optional) attributes
	 * @param 	bool	(optional) track submit flag
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-02
	 */
	public function __construct ($prefix, $formName, $method='post', $action='', $target='', array $attributes = array(), $trackSubmit = false, tx_tcaobjects_object $object = null, $formDefinition = '', array $onValidated = array(), array $onNotValidated = array(), array $onCancel = array(), $cancelButton = 'cancel') {

		$this->formname = $formName;
		$this->prefix = $prefix;

		parent::HTML_QuickForm ($formName, $method, $action, $target, $attributes, $trackSubmit);
		
		$this->registerElementType('rawstatic', t3lib_extMgm::extPath('tcaobjects') . 'res/class.tx_tcaobjects_qfRawStatic.php', 'tx_tcaobjects_qfRawStatic');

		$this->setupAdditionalRules();
		$this->setRequiredNote($GLOBALS['LANG']->sL('LLL:EXT:tcaobjects/res/locallang.xml:quickform.requiredNote'));

		if (!is_null($object)) {
			$this->set_object($object);
		}
		if (!empty($formDefinition)) {
			$this->set_formDefinition($formDefinition);
		}

		$this->onValidated = $onValidated;
		$this->onNotValidated = $onNotValidated;
		$this->onCancel = $onCancel;
		$this->cancelButton = $cancelButton;
	}
	
	public function generateIds() {
		foreach ($this->_elements as $element) {
			if (is_object($element) && method_exists($element, '_generateId')) {
				$element->_generateId();
				
			}
		}
	}


	public function processController() {

		$submitValues = $this->getSubmitValues(true);
		$cancel = $submitValues[$this->prefix][$this->formname][$this->cancelButton];

		if (!empty($cancel)) {
			/*******************************************************************
			 * Cancel (Returning to the default action
			 ******************************************************************/

			tx_pttools_assert::isObject($this->onCancel[0]);
			tx_pttools_assert::isNotEmptyString($this->onCancel[1]);

			if (!method_exists($this->onCancel[0], $this->onCancel[1])) {
				throw new tx_pttools_exception('Method "'.$this->onCancel[1].'" does not exist in object/class "'.get_class($this->onCancel[0]).'" for the "onCancel" action!');
			}

			$content = $this->onCancel[0]->{$this->onCancel[1]}($this, $this->onCancel[2]);

		} elseif ($this->validate()) {
			/*******************************************************************
			 * Form was submitted succesfully
			 ******************************************************************/

			tx_pttools_assert::isObject($this->onValidated[0]);
			tx_pttools_assert::isNotEmptyString($this->onValidated[1]);

			if (!method_exists($this->onValidated[0], $this->onValidated[1])) {
				throw new tx_pttools_exception('Method "'.$this->onValidated[1].'" does not exist in object/class "'.get_class($this->onValidated[0]).'" for the "onValidated" action!');
			}

			$content = $this->onValidated[0]->{$this->onValidated[1]}($this, $this->onValidated[2]);

		} else {
			/*******************************************************************
			 * Displaying the form
			 ******************************************************************/

			tx_pttools_assert::isObject($this->onNotValidated[0]);
			tx_pttools_assert::isNotEmptyString($this->onNotValidated[1]);

			if (!method_exists($this->onNotValidated[0], $this->onNotValidated[1])) {
				throw new tx_pttools_exception('Method "'.$this->onNotValidated[1].'" does not exist in object/class "'.get_class($this->onNotValidated[0]).'" for the "onNotValidated" action!');
			}

			$content = $this->onNotValidated[0]->{$this->onNotValidated[1]}($this, $this->onNotValidated[2]);

		}

		return $content;
	}



	/**
	 * Register some additional rules
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-02
	 */
	protected function setupAdditionalRules() {
		$this->registerRule('tca_unique', 'callback', 'rule_tca_unique', 'tx_tcaobjects_divForm');
		$this->registerRule('checktoken', 'callback', 'rule_checktoken', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_int', 'callback', 'rule_int', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_notint', 'callback', 'rule_notint', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_lower', 'callback', 'rule_lower', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_upper', 'callback', 'rule_upper', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_nospace', 'callback', 'rule_nospace', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_alpha', 'callback', 'rule_alpha', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_num', 'callback', 'rule_num', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_alphanum', 'callback', 'rule_alphanum', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_alphanum_x', 'callback', 'rule_alphanum_x', 'tx_tcaobjects_divForm');
		$this->registerRule('rule_fullAge', 'callback', 'rule_fullAge', 'tx_tcaobjects_divForm');
	}


	/**
	 * Sets a tcaobject to be used in this form
	 *
	 * @param 	tx_tcaobjects_object	tcaobject
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-02
	 */
	public function set_object(tx_tcaobjects_object $object) {
		$this->object = $object;
	}



	/**
	 * Returns the tcaobject used in this form
	 *
	 * @param	void
	 * @return 	tx_tcaobjects_object	tcaobject
	 * @since	2008-04-02
	 */
	public function get_object() {
		return $this->object;
	}



	/**
	 * Render the form
	 *
	 * @param 	HTML_QuickForm_Renderer	(optional) Renderer object
	 * @return 	string	HTML Output
	 */
	public function render(HTML_QuickForm_Renderer $renderer = null) {
		$this->generateIds();
		if (!is_null($renderer)) {
			$this->set_renderer($renderer);
		}
		$this->accept($this->renderer);
		return $this->renderer->toHtml();
	}



	/**
	 * Set the form definition as string or as typoscript (array)
	 *
	 * @param 	array|string	formDefiniton as string or typoscript array
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-02
	 */
	public function set_formDefinition($formDefinition) {
		if (is_array($formDefinition)) {
			$formDefinition = tx_tcaobjects_div::tsToQuickformString($formDefinition);
		}
		if (is_string($formDefinition)) {
			$this->formDefinition = $formDefinition;
		} else {
			throw new tx_pttools_exception('No valid formdefinition type');
		}
		$this->convertFormDefinitionToQuickformElements();
	}



	/**
	 * Set the renderer object
	 *
	 * @param 	tx_tcaobjects_iQuickformRenderer 	renderer
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-02
	 */
	public function set_renderer(HTML_QuickForm_Renderer $renderer) {
		if (!$renderer instanceof tx_tcaobjects_iQuickformRenderer) {
			throw new tx_pttools_exception('Renderer does not implement the "tx_tcaobjects_iQuickformRenderer" interface');
		}

		$this->renderer = $renderer;
	}



	/**
	 * Creates the rules for a single Quickform element. Has to be called after appending the element to the form.
	 *
	 * @param 	string			parameter (property ; altLabel ; specialtype ; content ; rules ; attributes)
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-14
	 */
	public function getQuickformElementRule($parameter) {

		list ($property, /* $altLabel */, /* $specialtype */ , /* $content */, $rules, $attributes) = t3lib_div::trimExplode(';', $parameter);
		
		$property = $this->object->resolveAlias($property);

		// create attribut list
		$attributes = t3lib_div::trimExplode(':', $attributes);
		foreach ($attributes as $attribute) {
			list ($key, $value) = t3lib_div::trimExplode('=', $attribute);
			$attributes[$key] = $value;
		}


		if (!empty($property) && $this->object->getConfig($property, 'max') > 0) {
			$notice = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tcaobjects/res/locallang.xml:quickform.rule.maxlength'), $this->object->getConfig($property, 'max'));
			$this->addRule($this->getElementName($property), $notice, 'maxlength', $this->object->getConfig($property, 'max'));
		}

		$evalFromTcaArray = empty($property) ? array() : $this->object->getEval($property);
		$evalFromConfigArray = t3lib_div::trimExplode(':', $rules);

		if (in_array('ignoretcaeval', $evalFromConfigArray)) {
			$evalFromTcaArray = array();
		}

		foreach (array_merge($evalFromTcaArray, $evalFromConfigArray) as $eval) {

			list ($eval, $message) = t3lib_div::trimExplode('=', $eval);

			switch ($eval) {

				case 'required': {
					$this->addRule($this->getElementName($property), $GLOBALS['LANG']->sL(!empty($message) ? $message : 'LLL:EXT:tcaobjects/res/locallang.xml:quickform.rule.required'), 'required');
				} break;

				case 'unique': {
					$this->addRule($this->getElementName($property), $GLOBALS['LANG']->sL(!empty($message) ? $message : 'LLL:EXT:tcaobjects/res/locallang.xml:quickform.rule.unique'), 'tca_unique', $this->object->getTable(). '->' . $property . '->' . $this->object['uid']);
				} break;

				// TYPO3 filters
				case 'int':
				case 'lower':
				case 'upper':
				case 'nospace':
				case 'alpha':
				case 'num':
				case 'alphanum':
				case 'alphanum_x':
				case 'trim': {
					$this->applyFilter($this->getElementName($property), array('tx_tcaobjects_divForm', 'filter_' . $eval));
				} break;

				// own rules
				case 'rule_int':
				case 'rule_notint':
				case 'rule_lower':
				case 'rule_upper':
				case 'rule_nospace':
				case 'rule_alpha':
				case 'rule_num':
				case 'rule_alphanum':
				case 'rule_alphanum_x': {
					$this->addRule($this->getElementName($property), $GLOBALS['LANG']->sL(!empty($message) ? $message : 'LLL:EXT:tcaobjects/res/locallang.xml:quickform.rule.' . $eval), $eval, $this->_table . '->' . $property . '->' . $this->uid);
				} break;

				// quickform rules
				case 'qf_rule_email':
				case 'qf_rule_emailorblank':
				case 'qf_rule_lettersonly':
				case 'qf_rule_alphanumeric':
				case 'qf_rule_numeric':
				case 'qf_rule_nopunctuation':
				case 'qf_rule_nonzero': {
					$rule = str_replace('qf_rule_', '', $eval);
					$this->addRule($this->getElementName($property), $GLOBALS['LANG']->sL(!empty($message) ? $message : 'LLL:EXT:tcaobjects/res/locallang.xml:quickform.rule.' . $eval), $rule, $this->_table . '->' . $property . '->' . $this->uid);
				} break;

				// unsupported TYPO3 evals:
				case 'password' :
				case 'date' :
				case 'uniqueInPid' : {
					// TODO: implement
					// throw new tx_pttools_exception('Eval "'.$eval.'" not implemented yet!');
				} break;

				case '': break;

				default: {
					throw new tx_pttools_exception('Eval "'.$eval.'" not valid!');
				}
			}
		}
	}



	/**
	 * Returns the element name
	 *
	 * @param 	string	property name
	 * @return 	string	element name
	 */
	public function getElementName($property) {
		$property = $this->object->resolveAlias($property);
		return $this->prefix . '[' . $this->formname . '][' . $property . ']';
	}



	/**
	 * Renders a single Quickform element specified by a paramater string
	 * Requires the tcaobject to be set before calling this method
	 *
	 * @param 	string			parameter (property ; altLabel ; specialtype ; content ; rules ; attributes ; comment)
	 * @return 	array			array of HTML_element objects
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-80
	 */
	public function createQuickformElement($parameter) {
		
		// TODO: switch to parameter object instead of a parameter string?

		// check requirements
		tx_pttools_assert::isObject($this->object, array('message' => 'No tcaobject set!'));
		tx_pttools_assert::isNotEmptyString($this->formname, array('message' => 'No formname set!'));
		tx_pttools_assert::isNotEmptyString($this->prefix, array('message' => 'No prefix set!'));

		list ($property, $altLabel, $specialtype, $content, /* $rules */, $attributes, $comment) = t3lib_div::trimExplode(';', $parameter);
		
		// urldecode text values
		$comment = urldecode($comment);
		$content = urldecode($content);

		tx_pttools_assert::isFalse(
			empty($property) && empty($specialtype), 
			array(
				'message' => 'Property AND specialtype are empty!"',
				'formname' => $this->formname,
				'parameter' => $parameter,
				'formdefinition' => $this->formDefinition
			)
		);

		$property = $this->object->resolveAlias($property);

		// if a property is used it must exist in the object (if not used by a specialtype)
		if (!empty($property) && !$this->object->offsetExists($property) && empty($specialtype)) {
			throw new tx_pttools_exception('Property "' . $property . '" not valid! ['.__FUNCTION__.']');
		}

		// create attribute list
		$sourceAttributes = t3lib_div::trimExplode(':', $attributes);
		$attributes = array();
		foreach ($sourceAttributes as $attribute) {
			list ($key, $value) = t3lib_div::trimExplode('=', $attribute);
			$attributes[$key] = $value;
		}
		// TODO attributes überall da dem element hinzufügen, wo es sinn macht
		if (!empty($property) && empty($specialtype)) {

			// Use the altLabel if set. If not use the label field defined in the TCA
			$label = $GLOBALS['LANG']->sL((!empty($altLabel)) ? $altLabel : $this->object->getCaption($property));



			switch (strtolower($this->object->getConfig($property, 'type'))){


				/***************************************************************
				 * Textarea
				 **************************************************************/
				case 'text': {
					$elements[] = HTML_QuickForm::createElement('textarea', $this->getElementName($property), $label, $attributes);
				} break;


				/***************************************************************
				 * File upload
				 **************************************************************/
				case 'group': {
					if ($this->object->getConfig($property, 'internal_type') == 'file') {

						if ($this->object->getConfig($property, 'maxitems') != 1) {
							throw new tx_pttools_exception('Maxitems > 1 not implemented yet'.' ['.__CLASS__."::".__FUNCTION__.'(...)]');
						}

						$this->setMaxFileSize(max($this->object->getConfig($property, 'max_size'), $this->getMaxFileSize()));

						$elements[] = HTML_QuickForm::createElement('file', $this->getElementName($property), $label);

						$propertyValue = $this->object[$property];
						if (!empty($propertyValue)) {
							foreach (t3lib_div::trimExplode(',', $propertyValue) as $key => $file) {

								// check if image file and render thumbnail if image
								$imagefile_ext = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']);
								$fI = t3lib_div::split_fileref($file);
								if (in_array($fI['fileext'], $imagefile_ext)) {
									$conf = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_tcaobjects.']['formThumbnail.'];
									$conf['file'] = $this->object->getConfig($property, 'uploadfolder') . DIRECTORY_SEPARATOR . $file;
									$elements[] = HTML_QuickForm::createElement('static', '', '', $GLOBALS['TSFE']->cObj->IMAGE($conf));
									$elements[] = HTML_QuickForm::createElement('checkbox', $this->getElementName('__qf_del_'.$key.'_'.$property), '', $GLOBALS['LANG']->sL('LLL:EXT:tcaobjects/res/locallang.xml:quickform.deleteFile'));
								} else {
									// TODO: render filelink?
								}

							}
						}
					} elseif ($this->object->getConfig($property, 'internal_type') == 'db') {

						if ($this->object->getConfig($property, 'maxitems') != 1) {
							throw new tx_pttools_exception('Maxitems > 1 not implemented yet'.' ['.__CLASS__."::".__FUNCTION__.'(...)]');
						}

						$elements[] = HTML_QuickForm::createElement('text', $this->getElementName($property), $label);
					} else {
						throw new tx_pttools_exception('Cannot process internal_type "' . $this->object->getConfig($property, 'internal_type'). '" found in property "' . $property . '"');
					}
				} break;


				/***************************************************************
				 * text input / password / date field
				 **************************************************************/
				case 'input': {
					if (in_array('password', $this->object->getEval($property))) {
						
						$elements[] = HTML_QuickForm::createElement('password', $this->getElementName($property), $label, $attributes);
						
					} elseif (in_array('date', $this->object->getEval($property))) {

						$minYearDelta = !empty($attributes['minYearDelta']) ? $attributes['minYearDelta'] : 5;
						$maxYearDelta = !empty($attributes['maxYearDelta']) ? $attributes['maxYearDelta'] : 5;

						$options = array(
							'language'  		=> !empty($attributes['language']) ? $attributes['language'] : 'en',
							'format'    		=> !empty($attributes['format']) ? $attributes['format'] : 'dMY',
							'minYear'   		=> !empty($attributes['minYear']) ? $attributes['minYear'] : date('Y') - $minYearDelta,
							'maxYear'   		=> !empty($attributes['maxYear']) ? $attributes['maxYear'] : date('Y') + $maxYearDelta,
							'addEmptyOption'   	=> !empty($attributes['addEmptyOption']) ? $attributes['addEmptyOption'] : false,
							'emptyOptionValue' 	=> !empty($attributes['emptyOptionValue']) ? $attributes['emptyOptionValue'] : '',
							'emptyOptionText'  	=> !empty($attributes['emptyOptionText']) ? $attributes['emptyOptionText'] : '&nbsp;',
						);
						$elements[] = HTML_QuickForm::createElement('date', $this->getElementName($property), $label, $options);

					} else {
						
						$tmpElement = HTML_QuickForm::createElement('text', $this->getElementName($property), $label, $attributes);
						$tmpElement->setComment($comment);
						$elements[] = $tmpElement;
					}
				} break;


				/***************************************************************
				 * Checkbox
				 **************************************************************/
				case 'check': {
					$elements[] = HTML_QuickForm::createElement('advcheckbox', $this->getElementName($property), $label);
				} break;


				/***************************************************************
				 * Radio boxes / select field
				 **************************************************************/
				case 'radio': // TODO: render as radio boxes!
				case 'select': {
						$selectionArray = array();
						$foreignTable = $this->object->getConfig($property, 'foreign_table');
						$items = $this->object->getConfig($property, 'items');

						if (is_array($items)) {

							foreach ($items as $selectItem) {
								$selectionArray[$selectItem[1]] = $GLOBALS['LANG']->sL($selectItem[0]);
							}

						} elseif (!empty($foreignTable)) {
							// TODO: "foreign_table_where" => "ORDER BY pages.uid", beachten

							$data = tx_tcaobjects_objectAccessor::selectCollection($foreignTable, '', '', $this->object->getConfig($property, 'foreign_label'));

							foreach ($data as $selectItem) {
								$selectionArray[$selectItem['uid']] = $selectItem[$this->object->getConfig($property, 'foreign_label')];
							}
						} else {
							throw new tx_pttools_exception('Property "'.$property.'" is an invalid field for type "select"');
						}

						$elementType = 'select';
						$addAttributes = array();
						if ($this->object->getConfig($property, 'size') > 1) {
							$addAttributes['size'] = $this->object->getConfig($property, 'size');
						}
						if ($this->object->getConfig($property, 'maxitems') > 1) {
							$addAttributes['multiple'] = 'multiple';
							$elementType = 'advmultiselect';

							$elements[] = HTML_QuickForm::createElement('hidden', $this->getElementName('__qf_ms_' . $property), 1);
						}

						$elements[] = HTML_QuickForm::createElement($elementType, $this->getElementName($property), $label, $selectionArray, $addAttributes);

				} break;

				default: {
						throw new tx_pttools_exception('Cannot process type "' . $this->object->getConfig($property, 'type') . '" (property "' . $property . '")');
				}

			}

		} else {

			// Process specialtypes
			switch (strtolower($specialtype)){

				case 'header': {
					$elements[] = HTML_QuickForm::createElement('header', '', $GLOBALS['LANG']->sL($content));
				} break;

				case 'static': {
					$elements[] = HTML_QuickForm::createElement('static', '', $GLOBALS['LANG']->sL($altLabel), $content);
				} break;

				case 'submit': {
					$elements[] = HTML_QuickForm::createElement('submit', $this->getElementName($content), $GLOBALS['LANG']->sL($altLabel));
				} break;

				case 'text': {
					$elements[] = HTML_QuickForm::createElement('text', $this->getElementName($property), $GLOBALS['LANG']->sL($altLabel));
				} break;

				case 'password': {
					$elements[] = HTML_QuickForm::createElement('password', $this->getElementName($property), $GLOBALS['LANG']->sL($altLabel));
				} break;

				case 'checkbox': {
					// TODO: use advcheckbox here?
					// TODO: extra field for unsetting checkbox?
					$elements[] = HTML_QuickForm::createElement('checkbox', $this->getElementName($property), $GLOBALS['LANG']->sL($altLabel), $GLOBALS['LANG']->sL($content));
				 } break;
				
				case 'static' : {
					$elements[] = HTML_QuickForm::createElement('static', $this->getElementName($property), $GLOBALS['LANG']->sL($altLabel), $content);
				} break;
				
				case 'rawstatic' : {
					$elements[] = HTML_QuickForm::createElement('rawstatic', $this->getElementName($property), $GLOBALS['LANG']->sL($altLabel), $content);
				} break;

				default: {
					throw new tx_pttools_exception('Unknown specialtype "' . $specialtype . '"');
				}

			}

		}

		// set property value as default value

		if ($this->object->offsetExists($property)) {
			if (strtolower($this->object->getConfig($property, 'type')) == 'input' && in_array('date', $this->object->getEval($property))) {
				$tstamp = $this->object[$property];

				if (!empty($tstamp)) {
					$defaultArray = array(
						// day fields
						'D' => date ('d', $tstamp),
						'l' => date ('d', $tstamp),
						'd' => date ('d', $tstamp),
						// month fields
						'M' => date ('n', $tstamp),
						'F' => date ('n', $tstamp),
						'm' => date ('n', $tstamp),
						// year field
						'Y' => date ('Y', $tstamp),
					);

					$this->setDefaults(array($this->getElementName($property) => $defaultArray));
				}
			} else {
				$this->setDefaults(array($this->getElementName($property) => $this->object[$property]));
			}
		}

		return $elements;
	}


	/**
	 * Convert form definition to quickform elements
	 *
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-02
	 */
	protected function convertFormDefinitionToQuickformElements() {

		tx_pttools_assert::isNotEmptyString($this->formDefinition, array('message' => 'No formDefinition set!'));

		$formDefinition = $this->formDefinition;

		$pattern = '/\[(.+)\]/U';
		$groups = array();
		if (preg_match_all($pattern, $this->formDefinition, $groups)) {
			$formDefinition = preg_replace($pattern, '--GROUP--', $formDefinition);
		}

		$parts = t3lib_div::trimExplode(',', $formDefinition);
		$groupcounter = 0;

		foreach ($parts as $part) {
			if ($part != '--GROUP--') {
				foreach ($this->createQuickformElement($part) as $element) {
					$this->addElement($element);
				}
				$this->getQuickformElementRule($part); // TODO: Test if rules work here (in groups)
			} else {

				list ($groupName, $groupLabel, $groupSeparator, $grouppartlist) = t3lib_div::trimExplode('|', $groups[1][$groupcounter]);
				$groupLabel = $GLOBALS['LANG']->sL($groupLabel);

				$groupparts = t3lib_div::trimExplode(',', $grouppartlist);
				$groupArray = array();
				foreach ($groupparts as $grouppart) {
					foreach ($this->createQuickformElement($grouppart) as $groupElement) {
						$groupArray[] = $groupElement;
					}
					$this->getQuickformElementRule($grouppart);
				}
				$this->addGroup($groupArray, $groupName, $groupLabel, $groupSeparator, true /* appendName */);
				$groupcounter++;
			}
		}

		if (isset($this->uid)) {
			$this->addElement('hidden', $this->getElementName('uid'), $this->object['uid']);
		}

		$this->addElement('hidden', $this->getElementName('__formToken'), tx_pttools_formReloadHandler::createToken());
		// $this->addElement('static', $this->getElementName('__messages'));

	}


	/**
	 * Set properties from HTML_Quickforms submit values
	 *
	 * @param 	HTML_QuickForm 	Quickform object
	 * @param 	string			prefix string
	 * @param 	string			formname
	 * @return	void
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function setObjectPropertiesFromSubmitValues() {

		// TODO: how to delete value?

		// process values
		$submitValues = $this->getSubmitValues(true);
		$submitValues = $submitValues[$this->prefix][$this->formname];

		// use token
		if (!self::$ignoreTokens) {
			if (tx_pttools_formReloadHandler::checkToken($submitValues['__formToken']) == false) {
				throw new tx_pttools_exception('Form was already submitted!', 0, 'Formname: "'.$this->formname.'"');
			}
		}

		// process new files (if any)
		$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions'); /* @var $fileFunc t3lib_basicFileFunctions */

		foreach ($this->_elements as $element) { /* @var $element HTML_QuickForm_element */
			if ($element->getType() == 'file' && $element->isUploadedFile()) { /* @var $element HTML_QuickForm_file */

				$property = tx_tcaobjects_divForm::getFieldFromName($element->getName());

				$fileFunc->init(array(), array('webspace' => array(	'allow' => $this->object->getConfig($property, 'allowed'),
																	 'deny' => $this->object->getConfig($property, 'disallowed'))));

				$val = $element->getValue();
				$filename = basename($val['name']);
				$destination = PATH_site . $this->object->getConfig($property, 'uploadfolder');
				$fI = t3lib_div::split_fileref($filename);
				if ($fileFunc->checkIfAllowed($fI['fileext'], $destination, $fI['file'])) {
					$filename = $fileFunc->getUniqueName($filename, $destination);
					$submitValues[$property] = basename($filename);
					$element->moveUploadedFile($destination, basename($filename));
				} else {
					throw new tx_pttools_exception('File/Extension (file: "'.$fI['file'].'", extension: "'.$fI['fileext'].'") is not allowed for destination "'.$destination.'"!');
				}
			}
		}

		// set properties
		foreach ($submitValues as $property => $value) {

			// reset empty multiselect fields
			if (t3lib_div::isFirstPartOfStr($property,'__qf_ms_')) {
				$realProperty = substr($property, strlen('__qf_ms_'));
				$this->object->offsetUnset($realProperty);
			}

			// delete files (from checkbox)
			if (t3lib_div::isFirstPartOfStr($property,'__qf_del_')) {
				$propertyParts = t3lib_div::trimExplode('_', substr($property, strlen('__qf_del_')));
				$key = array_shift($propertyParts);
				$realProperty = implode ('_', $propertyParts);

				$value = $this->object[$realProperty];

				$valueParts = t3lib_div::trimExplode(',', $value);

				unset($valueParts[$key]);

				$value = implode(',',$valueParts);

				$this->object[$realProperty] = $value;
			}

			if ($this->object->offsetExists($property)) {
				// preprocess values

				// select with maxitems > 1
				if (strtolower($this->object->getConfig($property, 'type')) == 'select' && $this->object->getConfig($property, 'maxitems') > 1 && is_array($value)) {
					$value = implode(',', $value);
				}

				// convert date into timestamp
				if (strtolower($this->object->getConfig($property, 'type')) == 'input' && in_array('date', $this->object->getEval($property))  && is_array($value)) {
					$value = tx_tcaobjects_div::convertQuickformDateToUnixTimestamp($value);
				}

				$this->object[$property] = $value;
			}
		}
	}


	/**
	 * Return the form name
	 *
	 * @return 	string	formname
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-06-23
	 */
	public function get_formname() {
		return $this->formname;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_quickform.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_quickform.php']);
}

?>