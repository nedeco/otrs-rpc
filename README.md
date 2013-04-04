# OTRS RPC

OTRS RPC is a small library that allows you to access OTRS via PHP. The following operations are currently supported:

* Getting a list of all tickets
* Getting a list of all ticket IDs
* Searching for tickets
* Getting a single ticket
* Looking up a ticket number based on a ticket ID
* Creating tickets
* Creating articles

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
define('OTRS_BASE_URL',            'http://192.168.9.60/otrs');
define('OTRS_USER',                'soap');
define('OTRS_PASSWORD',            'seife');
define('OTRS_DEFAULT_USER_ID',     1);
define('OTRS_DEFAULT_QUEUE_ID',    1);
define('OTRS_DEFAULT_PRIORITY_ID', 2);
```

## License

MIT.

See the included *LICENSE* file for details.
