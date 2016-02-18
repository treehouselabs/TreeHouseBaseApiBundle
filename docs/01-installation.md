## Installation

Install using composer:

```
composer require "treehouselabs/base-api-bundle"
```

Enable the bundle in the kernel:

```php
// app/AppKernel.php
$bundles[] = new TreeHouse\BaseApiBundle\TreeHouseBaseApiBundle();
```

## Configuration

Define the api host, and the host where the keystone token can be obtained.
Most of the time these will be the same.

```yaml
# app/config/config.yml
tree_house_base_api:
  host: %api_host%
  token_host: %api_host%
```

Add routing for both your own ApiBundle and Keystone's bundle:

```yaml
# app/config/routing.yml
acme_api:
  resource:     @AcmeApiBundle/Controller/
  type:         annotation
  host:         "{domain}"
  prefix:       /v1/
  requirements: { domain: %api_host% }
  defaults:     { domain: %api_host% }
tree_house_keystone:
  resource:     @TreeHouseKeystoneBundle/Resources/config/routing.yml
  host:         "{domain}"
  prefix:       /v1/
  requirements: { domain: %api_host% }
  defaults:     { domain: %api_host% }
```

Make sure you enable a firewall for the Keystone tokens url. You can leave your
Api open, but most of the time you'll want to secure it. To do so, simply enable
a different firewall with the keystone `authenticator` option set. Also don't forget
to make it stateless so no cookies will be sent!

```yaml
# app/config/security.yml
security:
  firewalls:
    tokens:
      pattern:       ^/tokens
      host:          %api_host%
      stateless:     true
      keystone-user: ~
    api:
      pattern:        ^/
      host:           %api_host%
      stateless:      true
      simple_preauth:
        authenticator: tree_house.keystone.token_authenticator
```

For more information about seting up Keystone authentication, see that [bundle's documentation][keystone-bundle].

[keystone-bundle]: https://github.com/treehouselabs/keystone-bundle/blob/master/src/TreeHouse/KeystoneBundle/Resources/doc/01-setup.md
