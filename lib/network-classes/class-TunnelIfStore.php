<?php
/*
 * Copyright (c) 2014-2017 Christophe Painchaud <shellescape _AT_ gmail.com>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.

 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
*/

/**
* @property $o TunnelInterface[]
 * @property PANConf $owner
*/
class TunnelIfStore extends ObjStore
{
    public static $childn = 'TunnelInterface';

    protected $fastMemToIndex=null;
    protected $fastNameToIndex=null;

    /**
     * @param $name string
     * @param $owner PANConf
     */
    public function __construct($name, $owner)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->classn = &self::$childn;
    }

    /**
     * @return TunnelInterface[]
     */
    public function getInterfaces()
    {
        return $this->o;
    }

    
    /**
     * Creates a new TunnelInterface in this store. It will be placed at the end of the list.
     * @param string $name name of the new TunnelInterface
     * @return TunnelInterface
     */
    public function newTunnelIf($name)
    {
        $tunnelIf = new TunnelInterface( $name, $this);
        $xmlElement = DH::importXmlStringOrDie($this->owner->xmlroot->ownerDocument, TunnelInterface::$templatexml);

        $tunnelIf->load_from_domxml($xmlElement);

        $tunnelIf->owner = null;
        $tunnelIf->setName($name);

        $this->addTunnelIf( $tunnelIf );

        return $tunnelIf;
    }


    /**
     * @param TunnelInterface $tunnelIf
     * @return bool
     */
    public function addTunnelIf( $tunnelIf )
    {
        if( !is_object($tunnelIf) )
            derr('this function only accepts TunnelInterface class objects');

        if( $tunnelIf->owner !== null )
            derr('Trying to add a TunnelInterface that has a owner already !');


        $ser = spl_object_hash($tunnelIf);

        if (!isset($this->fastMemToIndex[$ser]))
        {
            $tunnelIf->owner = $this;

            if( $this->xmlroot === null )
                $this->createXmlRoot();

            $this->xmlroot->appendChild($tunnelIf->xmlroot);

            return true;
        } else
            derr('You cannot add a TunnelInterface that is already here :)');

        return false;
    }

    /**
     * @param TunnelInterface $s
     * @return bool
     */
    public function API_addTunnelIf( $s )
    {
        $ret = $this->addTunnelIf($s);

        if( $ret )
        {
            $con = findConnectorOrDie($this);
            $xpath = $s->getXPath();
            #print 'XPATH: '.$xpath->textContent."\n";
            $con->sendSetRequest($xpath, DH::domlist_to_xml($s->xmlroot->childNodes, -1, false) );
        }

        return $ret;
    }

    public function createXmlRoot()
    {
        if( $this->xmlroot === null )
        {
            //TODO: 20180331 why I need to create full path? why it is not set before???
            $xml = DH::findFirstElementOrCreate('devices', $this->owner->xmlroot);
            $xml = DH::findFirstElementOrCreate('entry', $xml);
            $xml = DH::findFirstElementOrCreate('network', $xml);
            $xml = DH::findFirstElementOrCreate('interface', $xml);
            $xml = DH::findFirstElementOrCreate('tunnel', $xml);

            $this->xmlroot = DH::findFirstElementOrCreate('units', $xml);
        }
    }

    public function &getXPath()
    {
        $str = '';

        if( $this->owner->isDeviceGroup() || $this->owner->isVirtualSystem() )
            $str = $this->owner->getXPath();
        elseif( $this->owner->isPanorama() || $this->owner->isFirewall() )
            $str = '/config/shared';
        else
            derr('unsupported');

        //TODO: intermediate solution
        $str = '/config/devices/entry/network/interface';

        $str = $str.'/tunnel/units';

        return $str;
    }


    private function &getBaseXPath()
    {
        if ($this->owner->isPanorama() ||  $this->owner->isFirewall() )
        {
            $str = "/config/shared";
        }
        else
            $str = $this->owner->getXPath();

        //TODO: intermediate solution
        $str = '/config/devices/entry/network/interface';

        return $str;
    }

    public function &getTunnelIfStoreXPath()
    {
        $path = $this->getBaseXPath().'/tunnel/units';
        return $path;
    }

    public function rewriteXML()
    {
        if( count($this->o) > 0 )
        {
            if( $this->xmlroot === null )
                return;

            $this->xmlroot->parentNode->removeChild($this->xmlroot);
            $this->xmlroot = null;
        }

        if( $this->xmlroot === null )
        {
            if( count($this->o) > 0 )
            {
                $xml = DH::findFirstElementOrCreate('devices', $this->owner->xmlroot);
                $xml = DH::findFirstElementOrCreate('entry', $xml);
                $xml = DH::findFirstElementOrCreate('network', $xml);
                $xml = DH::findFirstElementOrCreate('interface', $xml);
                $xml = DH::findFirstElementOrCreate('tunnel', $xml);

                DH::findFirstElementOrCreate('units', $xml);
                #DH::findFirstElementOrCreate('tag', $this->owner->xmlroot);
            }

        }

        DH::clearDomNodeChilds($this->xmlroot);
        foreach( $this->o as $o)
        {
            if( !$o->isTmp() )
                $this->xmlroot->appendChild($o->xmlroot);
        }
    }
}