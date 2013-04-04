<?php
require_once './config.php';
require_once './lib/otrs_rpc.php';

$otrs_client = new OTRSRPC(OTRS_BASE_URL, OTRS_USER, OTRS_PASSWORD, OTRS_WEBSERVICE_NAME, OTRS_WEBSERVICE_NAMESPACE);

echo "# Ticket List\n";
$ticket_list = $otrs_client->ticket_list();
print_r($ticket_list);

echo "# Single Ticket\n";
$ticket_list_ids = $otrs_client->ticket_list_ids();
$ticket_id = $ticket_list_ids[array_rand($ticket_list_ids)];
$ticket = $otrs_client->ticket_get($ticket_id);
print_r($ticket);

echo "# Ticket Search\n";
$ticket_search = $otrs_client->ticket_search(array('TicketNumber' => '%'));
print_r($ticket_search);

echo "# Ticket Create\n";
$ticket_create = $otrs_client->ticket_article_create(array(
  'Ticket' => array(
    'Title' => 'Example',
    'CustomerUser' => 'demo'
  ),
  'Article' => array(
    'Subject' => 'Demo',
    'Body' => 'This is a demo'
  )
));
print_r($ticket_create);

echo "# Article Add\n";
$ticket_article = $otrs_client->ticket_article_add($ticket_create['TicketID'], array("Subject" => "New Article", "Body" => "DEMO DEMO DEMO"));
echo $ticket_article."\n";

echo "# Ticket Update\n";
$ticket_update = $otrs_client->ticket_update($ticket_create['TicketID'], array("PriorityID" => 1));
print_r($ticket_update);
