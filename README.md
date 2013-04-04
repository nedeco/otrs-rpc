# OTRS RPC

OTRS RPC is a small library that allows you to access OTRS via PHP. The following operations are currently supported:

* Getting a list of all tickets
* Getting a list of all ticket IDs
* Searching for tickets
* Reading, creating, updating tickets
* Reading, creating articles

One thing to note is that you **don't** have to supply key/value pairs like this:

```php
array("TicketObject", "TicketGet", "TicketID", $id);
```

You can use a hash instead:

```php
array(
  "TicketObject" => "TicketGet",
  "TicketID" => $id
);
```

See the included *demo.php* for small examples.

## Configuration

The following either have to or can be set in *config.php*:

```php
define('OTRS_BASE_URL',             'http://192.168.9.60/otrs');
define('OTRS_USER',                 'root@localhost');
define('OTRS_PASSWORD',             'root');
define('OTRS_WEBSERVICE_NAME',      'example.otrs_webservice');
define('OTRS_WEBSERVICE_NAMESPACE', 'urn:localhost:soap:functions');
define('OTRS_DEFAULT_QUEUE_ID',     1);
define('OTRS_DEFAULT_TYPE_ID',      1);
define('OTRS_DEFAULT_PRIORITY_ID',  2);
```

## Documentation

See [http://nedeco.github.com/otrs-rpc/](http://nedeco.github.com/otrs-rpc/) for the auto-generated PHPDoc.

## License

MIT.

See the included *LICENSE* file for details.
