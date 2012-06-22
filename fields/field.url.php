<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once(EXTENSIONS.'/url_field/extension.driver.php');



	class FieldURL extends Field
	{

		/**
		 * Compatible field types. Only Entry URL atm.
		 *
		 * @var array
		 */
		public $field_types;



		/*------------------------------------------------------------------------------------------------*/
		/*  Definition  */
		/*------------------------------------------------------------------------------------------------*/

		public function __construct(){
			parent::__construct();

			$this->_name = 'URL';
			$this->_required = 'yes';

			$this->field_types = array('entry_url');
		}

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$this->get('id')}` (
					`id` INT(11) UNSIGNED NOT null AUTO_INCREMENT,
					`entry_id` INT(11) UNSIGNED NOT null,
					`url_type` VARCHAR(255) DEFAULT null,
					`value` TEXT DEFAULT null,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Settings  */
		/*------------------------------------------------------------------------------------------------*/

		public function set($field, $value){
			if( $field == 'related_field_id' && !is_array($value) ){
				$value = explode(',', $value);
			}
			$this->_fields[$field] = $value;
		}

		public function findDefaults(){
			$this->set('related_field_id', array());
		}

		public function displaySettingsPanel(&$wrapper, $errors = null){
			parent::displaySettingsPanel($wrapper, $errors);

			$sections = SectionManager::fetch(null, 'ASC', 'sortorder');
			$options = array();

			if( is_array($sections) && !empty($sections) )
				foreach( $sections as $section ){
					$section_fields = $section->fetchFields();
					if( !is_array($section_fields) ) continue;

					$fields = array();
					foreach( $section_fields as $f ){
						if( in_array($f->get('type'), $this->field_types) ){
							$fields[] = array(
								$f->get('id'),
								is_array($this->get('related_field_id')) ? in_array($f->get('id'), $this->get('related_field_id')) : false,
								$f->get('label')
							);
						}
					}

					if( !empty($fields) ){
						$options[] = array(
							'label' => $section->get('name'),
							'options' => $fields
						);
					}
				}

			$label = Widget::Label(__('Values'));
			$label->appendChild(
				Widget::Select(
					'fields['.$this->get('sortorder').'][related_field_id][]',
					$options,
					array('multiple' => 'multiple')
				)
			);

			$wrapper->appendChild($label);

			$div = new XMLElement('div', null, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function commit(){
			if( !parent::commit() ) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if( $id === false ) return false;

			$fields['field_id'] = $id;

			$related_field_id = $this->get('related_field_id');
			$fields['related_field_id'] = empty($related_field_id) ? '' : implode(',', $related_field_id);

			Symphony::Database()->query("DELETE FROM `tbl_fields_{$handle}` WHERE `field_id` = '{$id}' LIMIT 1");
			return Symphony::Database()->insert($fields, "tbl_fields_{$handle}");
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Publish  */
		/*------------------------------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $prefix = null, $postfix = null){
			Extension_URL_Field::appendAssets();

			$related_field_id = array_filter($this->get('related_field_id'));
			if( empty($related_field_id) ) $data['url_type'] = 'external';

			$entry_ids = !is_null($data['value']) ? $entry_ids = array($data['value']) : array();
			$states = $this->findOptions($entry_ids);

			$base_name = 'fields'.$prefix.'['.$this->get('element_name').']';

			// Type
			$type = $data['url_type'];

			if( !empty($states) ){
				$div = new XMLElement('div', null, array('class' => 'url_type'));

				// Type internal
				$label = Widget::Label();
				$input = Widget::Input($base_name.'[url_type]'.$postfix, 'internal', 'radio');
				$input->setAttribute('data-target','internal');
				if( $type === 'internal' || empty($type) ) $input->setAttribute('checked', 'checked');
				$label->setValue(__('%s Internal', array($input->generate())));
				$div->appendChild($label);

				// Type external
				$label = Widget::Label();
				$input = Widget::Input($base_name.'[url_type]'.$postfix, 'external', 'radio');
				$input->setAttribute('data-target','external');
				if( $type === 'external' ) $input->setAttribute('checked', 'checked');
				$label->setValue(__('%s External', array($input->generate())));
				$div->appendChild($label);

				$wrapper->appendChild($div);
			}
			else{
				// Type external
				$input = Widget::Input($base_name.'[url_type]'.$postfix, 'external', 'hidden');
				$wrapper->appendChild($input);
			}


			// Value
			$label = Widget::Label($this->get('label'));
			if( $this->get('required') != 'yes' ) $label->appendChild(new XMLElement('i', __('Optional')));


			// Type Internal
			$options = array();
			if( $this->get('required') != 'yes' ) $options[] = array(null, false, null);

			if( !empty($states) ){
				foreach( $states as $s ){
					$group = array('label' => $s['name'], 'options' => array());
					foreach( $s['values'] as $id => $v ){
						$group['options'][] = array($id, in_array($id, $entry_ids), General::sanitize($v));
					}
					// sort entries alphabetically
					uasort($group['options'], function($a, $b){
						return $a[2] > $b[2];
					});
					$options[] = $group;
				}

				// sort sections alphabetically
				uasort($options, function($a, $b){
					return $a['label'] > $b['label'];
				});

				$label->appendChild(Widget::Select(
					$base_name.'[value_internal]'.$postfix,
					$options,
					array(
						'class' => 'value internal',
						'style' => ($type === 'internal' || empty($type)) ? '' : 'display:none'
					)
				));
			}


			// Type external
			$input = Widget::Input(
				$base_name.'[value_external]'.$postfix,
				$type === 'external' && !empty($data['value']) ? $data['value'] : '',
				'text',
				array(
					'class' => 'value external',
					'style' => $type !== 'external' ? 'display:none' : ''
				)
			);
			$label->appendChild($input);


			// Error
			if( !is_null($flagWithError) ){
				$wrapper->appendChild(Widget::Error($label, $flagWithError));
			}
			else{
				$wrapper->appendChild($label);
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Input  */
		/*------------------------------------------------------------------------------------------------*/

		public function checkPostFieldData($data, &$message, $entry_id = null){
			switch( $data['url_type'] ){
				case 'external':
					include(TOOLKIT.'/util.validators.php');
					if( !General::validateString($data['value_external'], $validators['URI']) ){
						$message = __("Invalid URI.");
						return self::__INVALID_FIELDS__;
					}

					if( $this->get('required') === 'yes' && empty($data['value_external']) ){
						$message = __('‘%s’ is a required field.', array($this->get('label')));
						return self::__MISSING_FIELDS__;
					}

					break;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null){
			$result = array(
				'url_type' => null,
				'value' => null
			);

			if( !is_array($data) || empty($data) ) return $result;

			$status = self::__OK__;

			if( isset($data['url_type']) ){
				$result = array(
					'url_type' => $data['url_type'],
					'value' => $data[ 'value_'.$data['url_type'] ]
				);
			}

			return $result;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Output  */
		/*------------------------------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(!is_array($data) || empty($data) || is_null($data['value'])) return;

			$result = new XMLElement($this->get('element_name'));
			$result->setAttribute('type',$data['url_type']);

			switch( $data['url_type'] ){
				case 'external':
					$result->setValue($data['value']);
					break;

				case 'internal':
					$result->setAttribute('id',$data['value']);
					$related_value = $this->findRelatedValues(array($data['value']));
					$result->setValue($related_value[0]['value']);
					break;
			}

			$wrapper->appendChild($result);
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			if (empty($data)) return;

			$link = '';
			$label = '';

			$related_field_id = array_filter($this->get('related_field_id'));

			if( empty($related_field_id) ) $data['url_type'] = 'external';

			switch( $data['url_type'] ){
				case 'external':
					$link = $data['value'];
					$label = $data['value'];
					break;

				case 'internal':
					$entry = EntryManager::fetch($data['value']);
					$entry = current($entry);

					$section = SectionManager::fetch($entry->get('section_id'));

					$link = SYMPHONY_URL.'/publish/'.$section->get('handle').'/edit/'.$data['value'];

					$related_value = $this->findRelatedValues(array($data['value']));
					$label = $related_value[0]['label'];

					break;
			}

			return Widget::Anchor($label, $link)->generate();


			$result = array();

			if(!is_array($data) || (is_array($data) && !isset($data['relation_id']))) {
				return parent::prepareTableValue(null);
			}

			if(!is_array($data['relation_id'])){
				$data['relation_id'] = array($data['relation_id']);
			}

			$result = $this->findRelatedValues($data['relation_id']);

			if(!is_null($link)){
				$label = '';
				foreach($result as $item){
					$label .= ' ' . $item['value'];
				}
				$link->setValue(General::sanitize(trim($label)));
				return $link->generate();
			}

			$output = '';

			foreach($result as $item){
				$link = Widget::Anchor($item['value'], sprintf('%s/publish/%s/edit/%d/', SYMPHONY_URL, $item['section_handle'], $item['id']));
				$output .= $link->generate() . ' ';
			}

			return trim($output);
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Utilities  */
		/*------------------------------------------------------------------------------------------------*/

		public function findOptions(array $existing_selection = NULL){
			$values = array();

			if( !is_array($this->get('related_field_id')) ) return $values;

			// find the sections of the related fields
			$sections = Symphony::Database()->fetch("
				SELECT DISTINCT (s.id), s.name, f.id as `field_id`
				FROM `tbl_sections` AS `s`
				LEFT JOIN `tbl_fields` AS `f` ON `s`.id = `f`.parent_section
				WHERE `f`.id IN ('".implode("','", $this->get('related_field_id'))."')
				ORDER BY s.sortorder ASC
			");

			if( is_array($sections) && !empty($sections) ){
				foreach( $sections as $section ){
					$group = array(
						'name' => $section['name'],
						'section' => $section['id'],
						'values' => array()
					);

					// build a list of entry IDs with the correct sort order
					EntryManager::setFetchSorting('date', 'DESC');
					$entries = EntryManager::fetch(NULL, $section['id'], null, 0, null, null, false, false);

					$results = array();
					foreach( $entries as $entry ){
						$results[] = (int) $entry['id'];
					}

					// if a value is already selected, ensure it is added to the list (if it isn't in the available options)
					if( !is_null($existing_selection) && !empty($existing_selection) ){
						$entries_for_field = $this->findEntriesForField($existing_selection, $section['field_id']);
						$results = array_merge($results, $entries_for_field);
					}

					if( is_array($results) && !empty($results) ){
						$related_values = $this->findRelatedValues($results);
						foreach( $related_values as $value ){
							$group['values'][$value['id']] = $value['label'];
						}
					}

					$values[] = $group;
				}
			}

			return $values;
		}

		public function findRelatedValues(array $relation_id = array()){
			// 1. Get the field instances from the SBL's related_field_id's
			// FieldManager->fetch doesn't take an array of ID's (unlike other managers)
			// so instead we'll instead build a custom where to emulate the same result
			// We also cache the result of this where to prevent subsequent calls to this
			// field repeating the same query.
			$where = ' AND id IN ('.implode(',', $this->get('related_field_id')).') ';
			$fields = FieldManager::fetch(null, null, 'ASC', 'sortorder', null, null, $where);
			if( !is_array($fields) ){
				$fields = array($fields);
			}

			if( empty($fields) ) return array();

			// 2. Find all the provided `relation_id`'s related section
			// We also cache the result using the `relation_id` as identifier
			// to prevent unnecessary queries
			$relation_id = array_filter($relation_id);
			if( empty($relation_id) ) return array();


			$relation_ids = Symphony::Database()->fetch(sprintf("
				SELECT e.id, e.section_id, s.name, s.handle
				FROM `tbl_entries` AS `e`
				LEFT JOIN `tbl_sections` AS `s` ON (s.id = e.section_id)
				WHERE e.id IN (%s)
				ORDER BY `e`.creation_date DESC
				",
				implode(',', $relation_id)
			));

			// 3. Group the `relation_id`'s by section_id
			$section_ids = array();
			$section_info = array();
			foreach( $relation_ids as $relation_information ){
				$section_ids[$relation_information['section_id']][] = $relation_information['id'];

				if( !array_key_exists($relation_information['section_id'], $section_info) ){
					$section_info[$relation_information['section_id']] = array(
						'name' => $relation_information['name'],
						'handle' => $relation_information['handle']
					);
				}
			}

			// 4. Foreach Group, use the EntryManager to fetch the entry information
			// using the schema option to only return data for the related field
			$relation_data = array();
			foreach( $section_ids as $section_id => $entry_data ){
				$schema = array();
				// Get schema
				foreach( $fields as $field ){
					if( $field->get('parent_section') == $section_id ){
						$schema = array($field->get('element_name'));
						break;
					}
				}

				EntryManager::setFetchSorting('date', 'DESC');
				$entries = EntryManager::fetch(array_values($entry_data), $section_id, null, null, null, null, false, true, $schema);

				// 5. Loop over the Entries fetching URL data
				foreach( $entries as $entry ){
					$url_data = $entry->getData($field->get('id'));

					$relation_data[] = array(
						'id' => $entry->get('id'),
						'section_handle' => $section_info[$section_id]['handle'],
						'section_name' => $section_info[$section_id]['name'],
						'value' => $url_data['value'],
						'label' => $url_data['label']
					);
				}
			}

			// 6. Return the resulting array containing the id, section_handle, section_name and value
			return $relation_data;
		}

		public function findEntriesForField(array $relation_id = array(), $field_id = null){
			if( empty($relation_id) || !is_array($this->get('related_field_id')) ) return array();

			try{
				// Figure out which `related_field_id` is from that section
				$relations = Symphony::Database()->fetchCol('id', sprintf("
						SELECT e.id
						FROM `tbl_fields` AS `f`
						LEFT JOIN `tbl_sections` AS `s` ON (f.parent_section = s.id)
						LEFT JOIN `tbl_entries` AS `e` ON (e.section_id = s.id)
						WHERE f.id = %d
						AND e.id IN (%s)
					",
					$field_id, implode(',', $relation_id), implode(',', $this->get('related_field_id'))
				));
			}
			catch( Exception $e ){
				return array();
			}

			return $relations;
		}

	}
