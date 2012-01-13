# DeviceWISE Public API Interface

This PHP class serves as an example of how to interact with the DeviceWISE public API.

Feel free to use this class in your own projects.

## Example Use

If you have an application token & an organization token:

```php
<?php
require 'dwapi.php';

$cloudlinkId = 'ORG.GATEWAY.NAME';
$userop      = 'Send SMS Message';
$inputs      = array('to'      => '5555555555',
                     'message' => 'This is a text message');

$configuration = array('endpoint'          => 'https://example.com/api',
                       'applicationToken'  => 'a1b2c3d4e5f6g7h8i9j0k1l2',
                       'organizationToken' => 'a2b1c0d9e8f7g6h5i4j3k2l1');

$api = new DwApi($configuration);

if (!$api->sessionId = $api->auth()) {
  foreach ($api->errors as $error) {
    echo "{$error}\n";
  }
  exit;
}

if (!$api->useropExec($cloudlinkId, $userop, $inputs)) {
  foreach ($api->errors as $error) {
    echo "{$error}\n";
  }
  exit;
}

echo "Executed {$userop} without error.\n";
exit;
?>
```

## Copyright

* DeviceWISE & CloudLINK are &copy; 2012 ILS Technology, LLC - All Rights Reserved
* This PHP class is &copy; 2012 ILS Technology, LLC under the GNU Public License version 2
