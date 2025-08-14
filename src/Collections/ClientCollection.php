<?php

declare(strict_types=1);

namespace Contazen\Collections;

use Contazen\Models\Client;

/**
 * Collection of Client models
 */
class ClientCollection extends Collection
{
    /**
     * Create Client instance from data
     * 
     * @param array $data
     * @return Client
     */
    protected static function createItem(array $data): Client
    {
        return Client::fromArray($data);
    }
    
    /**
     * Find client by email
     * 
     * @param string $email
     * @return Client|null
     */
    public function findByEmail(string $email): ?Client
    {
        return $this->find(function (Client $client) use ($email) {
            return strcasecmp($client->getAttribute('email', ''), $email) === 0;
        });
    }
    
    /**
     * Find client by CUI
     * 
     * @param string $cui
     * @return Client|null
     */
    public function findByCui(string $cui): ?Client
    {
        // Clean CUI - remove RO prefix if present
        $cui = preg_replace('/^RO/i', '', trim($cui));
        
        return $this->find(function (Client $client) use ($cui) {
            return $client->getAttribute('cui') === $cui;
        });
    }
    
    /**
     * Get B2B clients
     * 
     * @return array
     */
    public function getB2B(): array
    {
        return $this->filter(fn(Client $client) => $client->isB2B());
    }
    
    /**
     * Get B2C clients
     * 
     * @return array
     */
    public function getB2C(): array
    {
        return $this->filter(fn(Client $client) => $client->isB2C());
    }
    
    /**
     * Sort by name
     * 
     * @param string $order asc|desc
     * @return array
     */
    public function sortByName(string $order = 'asc'): array
    {
        $items = $this->items;
        
        usort($items, function (Client $a, Client $b) use ($order) {
            $nameA = $a->getName();
            $nameB = $b->getName();
            
            if ($order === 'asc') {
                return strcasecmp($nameA, $nameB);
            }
            
            return strcasecmp($nameB, $nameA);
        });
        
        return $items;
    }
}