# Cloudflare Assets
Extends `Pagefile` to use Cloudflare Images, Stream and R2 storage to serve files.

## Overview
The main purpose of this module is to allow ProcessWire to be used in an auto-scaling multi-instance environment. By serving file assets from Cloudflare, it is not necessary to have all file assets copied to all instances and we also get the benefits of serving assets from a CDN.

# How it works
When a `Pagefile` is added in the admin or via the API, it is uploaded to Cloudflare's R2 storage service. Additionally, if the file is an image, it is uploaded to Cloudflare Images, and if the file is a video it is uploaded to Cloudflare Stream. When a URL for the `Pagefile` is requested e.g. `$pagefile->url()`, the appropriate Cloudflare URL is returned.

As ProcessWire's admin still requires the file to be available locally, in a multi-instance setup if a file is not available it is downloaded from the 'master' copy in R2. Conversely, if an uploaded file is on the local filesystem, but not in Cloudflare's services, it will attempt to add it. This should allow the module to function where files and images have already been uploaded at the time of install.

## Installation
1. Download the [zip file](https://github.com/nbcommunication/CloudflareAssets/archive/master.zip) at Github or clone the repo into your `site/modules` directory.
2. If you downloaded the zip file, extract it in your `sites/modules` directory.
3. In your admin, go to Modules > Refresh, then Modules > New, then click on the Install button for this module.

**ProcessWire >= 3.0.210 and PHP >= 8.1.0 are required to use this module.**

## Configuration
To configure this module, go to Modules > Configure > CloudflareAssets. In another tab, open your [Cloudflare dashboard](https://dash.cloudflare.com).

The following settings are used for more than one service:
- **Account ID**: You should find this at the top of right sidebar on any of the main screens for R2 / Stream / Images.
- **Access Token**: This is used for API access for Stream and Images. See *Creating an API Token* below.
- **Custom domain**: This must be a domain managed and served by Cloudflare. See *Using a custom domain* below.

### Creating an API Token
To create an API token:
- Navigate to My Profile > API Tokens.
- Click *Create Token*.
- Under *API token templates* click **Read and write to Cloudflare Stream and Images**.
- Remove *Read* access for *Account Analytics*.
- In *Account Resources* select the account you will be using - it does not need access to all accounts.
- If you have a static IP or IP range and wish to limit access by this, enter it in *Client IP Address Filtering*.
- If you wish to time limit the token, set a range in *TTL* (not recommended for the purposes of this module).
- Click *Continue to summary*.
- Click *Create Token* and then copy the token to somewhere safe.

### Using a custom domain
To use a custom domain you need to either register a domain with Cloudflare or transfer a domain to your Cloudflare account.

#### Connecting your custom domain
Your custom domain needs to be manually connected to your bucket.
- In your bucket dashboard go to Settings > Public access > Custom Domains.
- Click *Connect Domain*.
- Add the domain* you wish to connect and click *Continue*.
- Allow Cloudflare to add the CNAME record by clicking *Connect domain*.

\*You can use any subdomain of a domain you have in your Cloudflare account. This may be preferable for CDN usage e.g. cdn.mydomain.com.

### R2
This information can be found in the R2 area of your Cloudflare account:
- **Bucket**: The bucket name cannot be changed once entered. Please make sure you enter the bucket name you want to use. If the bucket does not already exist, it will be created.
- **Access Key ID**
- **Secret Access Key**

To get your **Access Key ID** and **Secret Access Key**:
- Click on *Manage R2 API Tokens* at the top of the right sidebar.
- Click *Create API Token*.
- Give the token a name (e.g. ProcessWire)
- Set its permissions to *Edit*
- Add a *TTL* if your wish (not recommended for the purposes of this module)
- If you have a static IP or IP range and wish to limit access by this, enter it in *Client IP Address Filtering*.
- Click *Create API Token*.
- Copy the **Access Key ID** and **Secret Access Key** to the module configuration.
- Click *Finish*.

### Stream
- **Customer subdomain**: This can be found in the right sidebar on the Stream dashboard.
- **Allowed origins**: This defaults to `$config->httpHosts`. If you need to add more allowed origins, enter them on a new line.

### Images
- **Account hash**: This can be found in the right sidebar on the Images dashboard.
- **Use Image Delivery URL**: If enabled, Cloudflare's imagedelivery.net is used to server image instead of the custom domain.

### Only Uploads?
If this is enabled, assets will continue to be uploaded to Cloudflare if authorised, but will be served by the local filesystem. It switches off the `Pagefile::url()`, `Pagefile::httpUrl()` and `Pageimage::size()` hooks. If you encounter a problem with your Cloudflare setup, this option allows you to revert to the default without completely removing the integration.

## Usage
Once correctly configured, you shouldn't need to *use* the module at all; all `url()` and `httpUrl()` calls will be replaced by the appropriate URL from Cloudflare.

### Cloudflare Image Variants
If you wish to create and use an image variant, pass `cfImageVariant` to the Pageimage::size options.

```php
$image = $pageimage->size(123, 456, [
	'cfImageVariant' => true,
]);
```

*Please note that images cropped by width and height and using the focus point will not work, as these will quickly max out the variant usage (each different focus point requires a different variant). Only use image variants on predefined crops, preferably a single dimension.*

### Local URLs

```php
$url = $pagefile->localUrl;
$httpUrl = $pagefile->localHttpUrl;
```

### Migrating existing assets
Once installed, you may need to add existing assets to the Cloudflare in a batch, instead of relying on the built-in functionality which will upload page assets when the page is edited. This script should provide a starting point for this:

```php
// Run on a template of your choosing...
if($user->isSuperUser()) {

	$cf = $modules->get('CloudflareAssets');
	$fileFields = $fields->findByType('FieldtypeFile');

	// Do a dry run to determine the number of files to be evaluated/uploaded
	$dryRun = true;

	$result = [];
	// Use array_slice to limit the number of fields per request
	foreach(array_slice($fileFields, 0, count($fileFields)) as $f) {
		foreach($pages->find([
			"$f->name!=" => '',
			'include' => 'all',
			'check_access' => 0,
			//'limit' => 0, // Use these to limit the number of pages per request
			//'start' => 0,
		]) as $p) {

			$p->of(false);
			foreach($p->get($f->name) as $pagefile) {

				$url = $pagefile->localUrl;
				if($dryRun) {
					$uid = $pagefile->filedata($cf->className);
					$result[$url] = $uid ? 'already exists' : 'will be uploaded';
				} else {
					$uid = $cf->addToCloudflare($pagefile);
					$result[$url] = $uid ? 'uploaded' : 'upload failed';
				}
			}

			if($dryRun) {
				$p->of(true);
			} else {
				$pages->___save($p, [
					'noHooks' => true,
					'quiet' => true,
				]);
			}
		}
	}

	$result['count'] = count($result);

	header('Content-Type: text/plain');
	echo print_r($result, 1);

	die();
}
```

### Uninstalling
If you are uninstalling this module it is recommended that you run the following script on all server instances it is installed on. If you have just used it on one instance (and local copies should be present), this script will still ensure that the Cloudflare IDs are also removed from file data. This assumes the reason for uninstalling is that you plan to unsubscribe from Cloudflare's services and do not plan to re-enable for the site in future.

```php
// Run on a template of your choosing...
if($user->isSuperUser()) {

	$cf = $modules->get('CloudflareAssets');
	$fileFields = $fields->findByType('FieldtypeFile');

	// Do a dry run to determine the number of files to be processed
	$dryRun = false;

	// Do you want to delete the files from R2? Files in Images/Stream need to be deleted manually
	$r2Delete = false;

	$result = [];
	// Use array_slice to limit the number of fields per request
	foreach(array_slice($fileFields, 0, count($fileFields)) as $f) {
		foreach($pages->find([
			"$f->name!=" => '',
			'include' => 'all',
			'check_access' => 0,
			//'limit' => 0, // Use these to limit the number of pages per request
			//'start' => 0,
		]) as $p) {

			$p->of(false);
			foreach($p->get($f->name) as $pagefile) {

				if($dryRun) {

					$result[$pagefile->url] = $pagefile->localUrl;

				} else {

					$r = [];
					if($pagefile->filedata($cf->className)) {


						// Get from R2 if it doesn't exist
						if(!file_exists($pagefile->filename)) {
							if($files->filePutContents($pagefile->filename, fopen($pagefile->r2Path, 'r'))) {
								$r['Downloaded'] = $pagefile->r2Path;
							} else {
								$r['Download Failed'] = "$pagefile->r2Path ($pagefile->url)";
							}
						}

						if($r2Delete) {
							unlink($pagefile->r2Path);
							$r['Deleted'] = $pagefile->r2Path;
						}

						// Remove the CF ID
						$r[$pagefile->url] = $pagefile->localUrl;
						$pagefile->filedata(null, $cf->className);

					} else {

						$r[$pagefile->url] = 'No CF ID';
					}

					$result[] = $r;
				}
			}

			if($dryRun) {
				$p->of(true);
			} else {
				$pages->___save($p, [
					'noHooks' => true,
					'quiet' => true,
				]);
			}
		}
	}

	$result['count'] = count($result);

	header('Content-Type: text/plain');
	echo print_r($result, 1);

	die();
}
```

### Notes on Image Size Limitations
From https://developers.cloudflare.com/images/cloudflare-images/upload-images/formats-limitations/

- Maximum image dimension is 12,000 pixels.
- Maximum image area is limited to 100 megapixels (for example, 10,000×10,000 pixels).
- Image metadata is limited to 1024 bytes.
- Images have a 10 megabyte (MB) size limit.
- Animated GIFs, including all frames, are limited to 100 megapixels (MP).

Please ensure you configure your image fields so they do not exceed these limits.

## Cloudflare Documentation
- [R2](https://developers.cloudflare.com/r2/)
- [Stream](https://developers.cloudflare.com/stream/)
- [Images](https://developers.cloudflare.com/images/cloudflare-images/)
- [API](https://developers.cloudflare.com/api/)

## License
This project is licensed under the Mozilla Public License Version 2.0.
