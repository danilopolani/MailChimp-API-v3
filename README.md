# MailChimp API v3
This class will help you to manage the MailChimp API v3.

It's not complete, but the most important methods are included.

***

### Getting started

1. Install package with composer: `composer require grork/mailchimp=dev-master`. If you don't want to use composer, you can download the repository and extract it in your project folder.
2. Require the autoload with `require __DIR__ . '/vendor/autoload.php'` or with `require __DIR__ . '/src/Mailchimp.php` (or wherever you placed the folder)
3. Init the class with `$mailchimp = new Grork\Mailchimp( '<API-KEY-HERE>' )`. You can obtain your API Key logging into MailChimp > Account > Extras > API Keys > Create A Key

It's better to init the class with the following code, catching the exceptions:

	try {
		$mailchimp = new Grork\Mailchimp( '<API-KEY-HERE>' );
	} catch( Exception $e ) {
		die( 'Error: ' . $e->getMessage() );
	}

***

### Documentation

For the documentation, you can check the [wiki][1].

  [1]: https://github.com/DaniloPolani/MailChimp-API-v3/wiki
