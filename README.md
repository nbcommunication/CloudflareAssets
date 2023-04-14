# Cloudflare Assets
Extends `Pagefile` to use Cloudflare Images, Stream and R2 storage to serve files.

## Overview
The main purpose of this module is to allow ProcessWire to be used in an auto-scaling multi-instance environment. By serving file assets from Cloudflare, it is not necessary to have all file assets copied to all instances and we also get the benefits of serving assets from a CDN.

# How it works
When a `Pagefile` is added in the admin or via the API, it is uploaded to Cloudflare's R2 storage service. Additionally, if the file is an image, it is uploaded to Cloudflare Images, and if the file is a video it is uploaded to Cloudflare Stream. When a URL for the `Pagefile` is requested e.g. `$pagefile->url()`, the appropriate Cloudflare URL is returned.

As ProcessWire's admin still requires the file to be available locally, in a multi-instance setup if a file is not available it is downloaded from the 'master' copy in R2.

## Installation
1. Download the [zip file](https://github.com/nbcommunication/CloudflareAssets/archive/master.zip) at Github or clone the repo into your `site/modules` directory.
2. If you downloaded the zip file, extract it in your `sites/modules` directory.
3. In your admin, go to Modules > Refresh, then Modules > New, then click on the Install button for this module.

**ProcessWire >= 3.0.210 and PHP >= 8.1.0 are required to use this module.**

## Configuration
To configure this module, go to Modules > Configure > CloudflareAssets.

These settings are used for more than one service:
- **Account ID**: You should find this at the top of right sidebar on any of the main screens for R2 / Stream / Images
- **Access Token**: This is used for API access for Stream and Images. See *Creating an API Token* below.
- **Custom domain**: This must be a domain managed and served by Cloudflare. See *Creating a custom domain* below.

### Creating an API Token
To create an API token:
- Navigate to My Profile > API Tokens.
- Click *Create Token*.
- Click *Use template* for **Read and write to Cloudflare Stream and Images**.
- Remove *Read* access for *Account Analytics*.
- In *Account Resources* select the account you will be using - it does not need access to all accounts.
- If you have a static IP or IP range and wish to limit access by this, enter it in *Client IP Address Filtering*.
- If you wish to time limit the token, set a range in *TTL* (not recommended for the purposes of this module).
- Click *Continue to summary*.

### Creating a custom domain
Coming soon: These instructions will be written in the near future (May 2023).

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

#### Flexible Variants
At the moment, in order for image variants to work correctly, *Flexible variants* needs to be enabled in Cloudflare. You can do this in Images > Variants.

## Usage
Once correctly configured, you shouldn't need to *use* the module at all; all `url()` and `httpUrl()` calls will be replaced by the appropriate URL from Cloudflare. Calls to `Pageimage::size()` will skip generating a variant on the local filesystem and will instead return a flexible variant URL from Cloudflare. Currently not all options from `size()` are mapped to flexible variant options, but width, height and focus point will be used.

### Notes on Image Size Limitations
From https://developers.cloudflare.com/images/cloudflare-images/upload-images/formats-limitations/

- Maximum image dimension is 12,000 pixels.
- Maximum image area is limited to 100 megapixels (for example, 10,000×10,000 pixels).
- Image metadata is limited to 1024 bytes.
- Images have a 10 megabyte (MB) size limit.
- Animated GIFs, including all frames, are limited to 100 megapixels (MP).

## Cloudflare Documentation
- [R2](https://developers.cloudflare.com/r2/)
- [Stream](https://developers.cloudflare.com/stream/)
- [Images](https://developers.cloudflare.com/images/cloudflare-images/)
- [API](https://developers.cloudflare.com/api/)

## License
This project is licensed under the Mozilla Public License Version 2.0.
