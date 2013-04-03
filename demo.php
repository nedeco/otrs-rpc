<?php
require_once './config.php';
require_once './lib/otrs_rpc.php';

$otrs_client = new OTRSRPC(OTRS_BASE_URL, OTRS_USER, OTRS_PASSWORD);

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
