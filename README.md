# orcid-php
PHP Library for ORCID, supporting v2 API connections

This library was started to support the ORCID OAuth2 authentication workflow. It also supports basic profile access, but is a work in progress. More features are to come as needed by the developer or requested/contributed by other interested parties.

This fork of the original [hubzero/orcid-php](https://github.com/hubzero/orcid-php) project adds support for switching between v1.2 and v2.0 APIs, and defaults to 2.0.

## Usage

### OAuth2

#### 3-Legged Oauth Authorization

To go through the 3-legged oauth process, you must start by redirecting the user to the ORCID authorization page.

```php
// Set up the config for the ORCID API instance
$oauth = new Oauth;
$oauth->setClientId($clientId)
      ->setScope('/authenticate')
      ->setState($state)
      ->showLogin()
      ->setRedirectUri($redirectUri);

// Create and follow the authorization URL
header("Location: " . $oauth->getAuthorizationUrl());
```

Most of the options described in the ORCID documentation (http://members.orcid.org/api/customize-oauth-login-screen) concerning customizing the user authorization experience are encapsulated in the OAuth class.

Once the user authorizes your app, they will be redirected back to your redirect URI. From there, you can exchange the authorization code for an access token.

```php
if (!isset($_GET['code']))
{
	// User didn't authorize our app
	throw new Exception('Authorization failed');
}

$oauth = new Oauth;
$oauth->setClientId($clientId)
      ->setClientSecret($clientSecret)
      ->setRedirectUri($redirectUri);

// Authenticate the user
$oauth->authenticate($_GET['code']);

// Check for successful authentication (v2.0)
if ($oauth->isAuthenticated())
{
	$orcid = new Profile($oauth, '2.0');

	// Get ORCID iD
	$id = $orcid->id();
}
```

This example uses the ORCID public API. A members API is also available, but the OAuth process is essentially the same.

#### Client Credential Authorization

To be implemented...

### Profile

As alluded to in the samples above, once successfully authenticated via OAuth, you can make subsequent requests to the other public/member APIs. For example:

```php
$orcid = new Profile($oauth, '2.0');

// Get ORCID profile details
$id    = $orcid->id();
$email = $orcid->email();
$name  = $orcid->fullName();
```

By default, a Profile object will be created usiong the v2.0 API data structure. Passing the value ``'1.2'`` as the second, version, parameter, will use the older, v1.2 API record structure.

The profile class currently only supports a limited number of helper methods for directly accessing elements from the profile data. This will be expanded upon as needed. The raw JSON data from the profile output is available by calling the raw() method.

Note that some fields (like email) may return null if the user has not made that field available.

### Environment and API types

ORCID supports two general API endpoints.  The first is their public API, and a second is for registered ORCID members (membership in this scenario does not simply mean that you have an ORCID account).  The public API is used by default and currently supports all functionality provided by the library.  You can, however, switch to the member API by calling:

```php
$oauth = new Oauth;
$oauth->useMembersApi();
```

If you explicitly want to use the public API, you can do so by calling:

```php
$oauth = new Oauth;
$oauth->usePublicApi();
```

ORCID also supports a sandbox environment designed for testing.  To use this environment, rather than the production environment (which is default), you can call the following command:

```php
$oauth = new Oauth;
$oauth->useSandboxEnvironment();
```

The counterpart to this function, though not explicitly necessary, is:

```php
$oauth = new Oauth;
$oauth->useProductionEnvironment();
```
