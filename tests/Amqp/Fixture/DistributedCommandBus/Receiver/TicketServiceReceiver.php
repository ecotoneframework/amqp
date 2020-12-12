<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver;

use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\Distributed;
use Ecotone\Modelling\Annotation\QueryHandler;

class TicketServiceReceiver
{
    const CREATE_TICKET_ENDPOINT = "createTicket";
    const GET_TICKETS_COUNT      = "getTicketsCount";

    private array $tickets = [];

    #[Distributed]
    #[CommandHandler(self::CREATE_TICKET_ENDPOINT)]
    public function registerTicket(string $ticket) : void
    {
        $this->tickets[] = $ticket;
    }

    #[QueryHandler(self::GET_TICKETS_COUNT)]
    public function getTickets() : int
    {
        return count($this->tickets);
    }
}