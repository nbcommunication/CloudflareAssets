<?php namespace ProcessWire;

/**
 * Cloudflare Assets Configuration
 *
 */

class CloudflareAssetsConfig extends ModuleConfig {

	/**
	 * Returns default values for module variables
	 *
	 * @return array
	 *
	 */
	public function getDefaults() {
		return [
			'allowedOriginsList' => implode("\n", $this->wire()->config->httpHosts),
			'imageDelivery' => 0,
			'onlyUploads' => 0,
		];
	}

	/**
	 * Returns inputs for module configuration
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getInputfields() {

		$config = $this->wire()->config;
		$modules = $this->wire()->modules;

		$inputfields = parent::getInputfields();

		// Get the module this configures
		$module = $modules->get(str_replace('Config', '', $this->className));

		$inputfields->add([
			'type' => 'text',
			'name' => 'accountId',
			'value' => $this->accountId,
			'required' => true,
			'label' => $this->_('Account ID'),
			'icon' => 'star',
			'columnWidth' => 50,
		]);

		$inputfields->add([
			'type' => 'text',
			'name' => 'accessToken',
			'value' => $this->accessToken,
			'required' => true,
			'label' => $this->_('Access Token'),
			'icon' => 'key',
			'attr' => [
				'type' => 'password',
			],
			'columnWidth' => 50,
		]);

		$inputfields->add([
			'type' => 'text',
			'name' => 'domain',
			'value' => $this->domain,
			'required' => true,
			'label' => $this->_('Custom domain'),
			'notes' => $this->_('The custom domain used to allow public access to files in R2 storage.') . "\n" .
				$this->_('This domain will also be used for image delivery.'),
			'icon' => 'globe',
		]);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->label = $this->_('R2');
		$fieldset->icon = 'database';

		$hasBucket = (bool) $this->bucket;

		$fieldset->add([
			'type' => $hasBucket ? 'markup' : 'text',
			'name' => 'bucket',
			'value' => $this->bucket,
			'required' => !$hasBucket,
			'label' => $this->_('Bucket'),
			'notes' => $this->_('Once a bucket name has been entered it cannot be changed.') .
				($hasBucket ? '' : "\n" . $this->_('Please ensure the bucket name you enter is the one you wish to use.')),
			'icon' => 'hdd-o',
		]);

		$fieldset->add([
			'type' => 'text',
			'name' => 'accessKeyId',
			'value' => $this->accessKeyId,
			'required' => true,
			'label' => $this->_('Access Key ID'),
			'icon' => 'star',
			'columnWidth' => 50,
		]);

		$fieldset->add([
			'type' => 'text',
			'name' => 'secretAccessKey',
			'value' => $this->secretAccessKey,
			'required' => true,
			'label' => $this->_('Secret Access Key'),
			'icon' => 'key',
			'attr' => ['type' => 'password'],
			'columnWidth' => 50,
		]);

		$inputfields->add($fieldset);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->label = $this->_('Stream');
		$fieldset->icon = 'play-circle';

		$fieldset->add([
			'type' => 'text',
			'name' => 'customerSubdomain',
			'value' => $this->customerSubdomain,
			'label' => $this->_('Customer subdomain'),
			'icon' => 'link',
		]);

		$fieldset->add([
			'type' => 'textarea',
			'name' => 'allowedOriginsList',
			'value' => $this->allowedOriginsList,
			'label' => $this->_('Allowed Origins'),
			'description' => $this->_('The origins allowed to display the video.'),
			'notes' => implode("\n", [
				$this->_('Please enter each origin on a new line.'),
				$this->_('Use * for wildcard subdomains. Leave empty to allow the video to be viewed on any origin.'),
			]),
			'icon' => 'globe',
		]);

		$inputfields->add($fieldset);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->label = $this->_('Images');
		$fieldset->icon = 'picture-o';

		$fieldset->add([
			'type' => 'text',
			'name' => 'accountHash',
			'value' => $this->accountHash,
			'label' => $this->_('Account hash'),
			'icon' => 'hashtag',
		]);

		$deliveryUrl = 'https://imagedelivery.net/';
		$fieldset->add([
			'type' => 'checkbox',
			'name' => 'imageDelivery',
			'value' => $this->imageDelivery,
			'label' => $this->_('Use Image Delivery URL?'),
			'checkboxLabel' => sprintf($this->_('Serve my images from %s'), $deliveryUrl),
			'description' => sprintf($this->_('If this is enabled, the custom domain entered will not be used and images will be served from %s.'), $deliveryUrl),
			'icon' => 'globe',
			'collapsed' => 2,
		]);

		$inputfields->add($fieldset);

		$inputfields->add([
			'type' => 'checkbox',
			'name' => 'onlyUploads',
			'value' => $this->onlyUploads,
			'label' => $this->_('Only Uploads?'),
			'checkboxLabel' => $this->_('Serve assets from the local filesystem'),
			'description' => $this->_('If this is enabled, assets will continue to be uploaded to Cloudflare if authorised by the details entered above, but will be served by the local filesystem.'),
			'notes' => $this->_('If you encounter a problem with your Cloudflare setup, this option allows you to revert to the default without completely removing the integration.'),
			'icon' => 'cloud-upload',
			'collapsed' => 2,
		]);

		return $inputfields;
	}
}
