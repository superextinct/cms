<?php
namespace Blocks;

/**
 * Links block type class
 */
class LinksBlockType extends BaseBlockType
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		$name = Blocks::t('Links');
		if (isset($this->model))
		{
			$linkType = $this->_getLinkType();
			if ($linkType)
			{
				$name .= ' ('.$linkType->getName().')';
			}
		}
		return $name;
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'type'             => array(AttributeType::ClassName, 'required' => true, 'default' => 'Entries'),
			'addLabel'         => array(AttributeType::String, 'required' => true, 'default' => 'Add Links'),
			'removeLabel'      => array(AttributeType::String, 'required' => true, 'default' => 'Remove Links'),
			'limit'            => array(AttributeType::Number, 'min' => 0),
			'linkTypeSettings' => array(AttributeType::Mixed, 'default' => array()),
		);
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$linkType = $this->_getLinkType();
		if (!$linkType)
		{
			$linkType = blx()->links->getLinkType('Entries');
		}

		return blx()->templates->render('_components/blocktypes/Links/settings', array(
			'linkType' => new LinkTypeVariable($linkType),
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Preprocesses the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function preprocessSettings($settings)
	{
		if (isset($settings['types'][$settings['type']]))
		{
			$linkTypeSettings = $settings['types'][$settings['type']];
		}
		else
		{
			$linkTypeSettings = array();
		}

		unset($settings['types']);

		// Give the link type a chance to pre-process any of its settings
		$linkType = blx()->links->getLinkType($settings['type']);
		$settings['linkTypeSettings'] = $linkType->preprocessSettings($linkTypeSettings);

		return $settings;
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @param int|null $entityId;
	 * @return string
	 */
	public function getInputHtml($name, $value, $entityId = null)
	{
		$linkType = $this->_getLinkType();

		if ($entityId)
		{
			$entities = blx()->links->getLinkedEntities($this->model, $entityId);
		}
		else
		{
			$entities = array();
		}

		$settings = $this->getSettings()->getAttributes();
		$settings['addLabel'] = Blocks::t($settings['addLabel']);
		$jsonSettings = JsonHelper::encode($settings);

		$entityIds = JsonHelper::encode(array_keys($entities));

		blx()->templates->includeJs('new Blocks.ui.LinksBlock("'.$name.'", '.$jsonSettings.', '.$entityIds.');');

		return blx()->templates->render('_components/blocktypes/Links/input', array(
			'name'     => $name,
			'value'    => $value,
			'linkType' => $this->_getLinkType(),
			'settings' => $this->getSettings(),
			'entities' => $entities,
		));
	}

	/**
	 * Performs any additional actions after the entity has been saved.
	 */
	public function onAfterEntitySave()
	{

	}

	/**
	 * Returns the link type
	 *
	 * @return BaseLinkType|null
	 */
	private function _getLinkType()
	{
		$linkType = blx()->links->getLinkType($this->getSettings()->type);
		if ($linkType)
		{
			$linkType->setSettings($this->getSettings()->linkTypeSettings);
			return $linkType;
		}
	}
}
