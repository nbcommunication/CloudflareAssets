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
		$input = $this->wire()->input;
		$modules = $this->wire()->modules;

		$inputfields = parent::getInputfields();

		// Get the module this configures
		$module = $modules->get(str_replace('Config', '', $this->className));

		if($input->post->bool('_clearStats')) {
			$this->wire()->cache->deleteFor($module, 'usage*');
			$this->message($this->_('Cache cleared and usage statistics regenerated'));
			$this->wire()->session->redirect($input->url(true));
		}

		$usage = $module->getUsageStatistics();

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

		$_getNotes = function($key) use ($usage) {

			$notes = [
				sprintf(
					$this->_('There are currently **%s** unique records in the database.'),
					number_format($usage["{$key}Local"]),
					$key
				),
			];

			if($usage["{$key}Variations"] ?? 0) {
				$notes[] = sprintf(
					$this->_('**%s** of these are variations.'),
					number_format($usage["{$key}Variations"])
				);
			}

			if($usage["{$key}Duplicate"]) {
				$notes[] = sprintf(
					$this->_('**%s** of these are duplicates.'),
					number_format($usage["{$key}Duplicate"])
				);
			}

			if($usage["{$key}Missed"]) {
				$notes[] = sprintf(
					$this->_('**%1$s** of these are not connected to Cloudflare %2$s.'),
					number_format($usage["{$key}Missed"]),
					ucfirst($key)
				);
			}

			return implode("\n", $notes);
		};

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->label = $this->_('Stream');
		$fieldset->icon = 'play-circle';
		$fieldset->description = sprintf(
			$this->_n(
				$this->_('Cloudflare Stream contains **%s** video.'),
				$this->_('Cloudflare Stream contains **%s** videos.'),
				$usage['streamCount']
			),
			number_format($usage['streamCount'])
		);
		$fieldset->notes = $_getNotes('stream');

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
		$fieldset->description = sprintf(
			$this->_('You are currently using **%1$s** of **%2$s** images, and **%3$s** of **%4$s** image variants.'),
			number_format($usage['imagesCount']),
			number_format($usage['imagesAllowed']),
			number_format($usage['variantsCount']),
			number_format($usage['variantsAllowed'])
		);

		$fieldset->notes = $_getNotes('images');

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

		$variantsCount = $usage['variantsCount'];
		if($variantsCount) {

			$icon = 'star';
			$used = $usage['variantsCount'] / $usage['variantsAllowed'];
			if($used < 0.1) {
				$icon .= '-o';
			} else if($used < 0.8) {
				$icon .= '-half-o';
			}

			$keys = [];
			$values = [];
			foreach($usage['variantsUsage'] as $variant => $variantUsage) {
				if($variant === 'public') continue;
				$value = [];
				foreach($variantUsage as $field => $count) {
					$keys[$field] = ($keys[$field] ?? 0) + $count;
					$value[$field] = $count;
				}
				$values[$variant] = $value;
			}

			arsort($keys);
			ksort($values);

			$rows = [];
			foreach($values as $variant => $value) {
				$row = [];
				foreach(array_keys($keys) as $field) {
					$row[] = $value[$field];
				}
				$row[] = array_sum($row);
				$rows[] = array_merge([$variant], $row);
			}
			$labelTotal = $this->_('Total');

			$row = [];
			$headers = [$this->_('Variant [public]')];
			foreach($keys as $field => $count) {
				$row[] = $count;
				$headers[] = "$field [{$usage['variantsUsage']['public'][$field]}]";
			}
			$row[] = array_sum($row);
			$rows[] = array_merge([$labelTotal], $row);

			$headers[] = $labelTotal;

			$table = $modules->get('MarkupAdminDataTable');
			$table->setEncodeEntities(false);
			$table->headerRow($headers);
			foreach($rows as $row) $table->row($row);

			$fieldset->add([
				'type' => 'markup',
				'name' => 'variantsUsage',
				'value' => $table->render(),
				'label' => $this->_('Variant Usage'),
				'icon' => $icon,
				'collapsed' => 1,
			]);
		}

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

		$inputfields->add([
			'type' => 'checkbox',
			'name' => '_clearStats',
			'value' => 0,
			'label' => $this->_('Clear the cached statistics?'),
			'checkboxLabel' => $this->_('Clear and regenerate'),
			'description' => $this->_('If you have deleted assets in the Cloudflare admin, check this box and click Submit to regenerate usage stats.'),
			'icon' => 'refresh',
			'collapsed' => 2,
		]);

		return $inputfields;
	}
}
