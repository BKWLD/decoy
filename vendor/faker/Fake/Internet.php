<?php
/**
 * Faker {{version}}
 * A Fake Text Generator
 * <http://github.com/maetl/Faker>
 *
 * Copyright (c) 2011, Mark Rickerby <http://maetl.net>
 * All rights reserved.
 * 
 * This library is free software; refer to the terms in the LICENSE file found
 * with this source code for details about modification and redistribution.
 */

/**
 * Fake internet addresses.
 */
class Fake_Internet extends Fake {
	
	/**
	 * network services
	 */
	protected $network_services = 'ftp ssh telnet shell smtp mail time ns dns tacacs bootp dhcp www kerberos pop pop2 pop3 imap nfs ntp imap imap2 snmp irc imap3 https snpp isakmp ipp printer fileserver logs log loghost syslog news nntp ldap ldaps socks vpn sql db radius cvs svn xmpp x11 backup';
	
	/**
	 * From: http://publicsuffix.org
	 */
	protected $top_level_domains = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','as','asia','at','aw','ax','az','ba','bb','be','bf','bg','bh','bi','biz','bj','bm','bo','br','bs','bt','bw','by','bz','ca','cat','cc','cd','cf','cg','ch','ci','cl','cm','cn','co','com','coop','cr','cu','cv','cx','cz','de','dj','dk','dm','do','dz','ec','edu','ee','eg','es','eu','fi','fm','fo','fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gov','gp','gq','gr','gs','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','im','in','info','int','io','iq','ir','is','it','je','jo','jobs','jp','kg','ki','km','kn','kr','ky','kz','la','lc','li','lk','local','ls','lt','lu','lv','ly','ma','mc','md','me','mg','mh','mil','mk','ml','mn','mo','mobi','mp','mq','mr','ms','mu','mv','mw','mx','my','na','name','nc','ne','net','nf','nl','no','nr','nu','org','pa','pe','pf','ph','pk','pl','pn','pr','pro','ps','pt','pw','re','ro','rs','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','so','sr','st','su','sy','sz','tc','td','tel','tf','tg','th','tj','tk','tl','tm','tn','to','travel','tt','tv','tw','ua','ug','us','uz','va','vc','vg','vi','vn','vu','ws','xxx');
	
	/**
	 * From: http://publicsuffix.org
	 */
	protected $domain_suffixes = array('org.an','edu.an','tas.au','pp.az','biz.az','org.ba','edu.ba','com.ba','h.bg','com.bi','edu.bm','net.bm','gob.bo','mil.bo','bmd.br','cim.br','eng.br','g12.br','ggf.br','ind.br','org.br','pro.br','gov.bs','com.bt','of.by','net.bz','qc.ca','md.ci','hb.cn','js.cn','br.com','no.com','uy.com','org.dm','org.do','com.dz','art.dz','gov.ee','med.ee','edu.ge','edu.gh','gov.gh','org.gh','edu.gi','org.gn','net.gn','org.gr','net.ht','med.ht','net.im','ac.im','edu.in','com.iq','co.ir','org.ir','sch.ir','int.is','mil.jo','gov.km','edu.km','tra.kp','ac.kr','re.kr','edu.ky','org.lb','org.lk','grp.lk','org.ls','gov.lt','org.lv','edu.ly','ac.ma','edu.mg','edu.mk','gov.mk','inf.mk','gov.mu','int.mv','pro.mv','biz.mw','int.mw','ca.na','ws.na','com.ng','tm.no','med.pa','net.pe','co.pn','edu.pn','pro.pr','ac.pr','com.ps','org.pt','org.rs','org.ru','gov.rw','com.rw','int.rw','org.sa','gov.sa','b.se','d.se','h.se','m.se','z.se','net.sg','com.sn','co.th','net.th','org.tj','org.to','ne.tz','net.ua','dp.ua','km.ua','co.ug','dni.us','nsn.us','ca.us','dc.us','ky.us','ms.us','nc.us','nj.us','ny.us','vi.us','co.uz','mil.vc','com.vi');
	
	/**
 	 * A fake username.
	 */
	public function username() {
		$list = Faker_Corpus::getList('Person/last_name');
		return $this->lexicalize($list);
	}

	/**
 	 * A fake computer hostname
	 */
	public function hostname() {
		//
	}
	
	/**
 	 * A fake server name.
	 * Some service name such as mail, dns, etc, prepended to a fake domain name.
	 */
	public function server_name() {
		//
	}

	/**
	 * A fake domain name.
	 */
	public function domain_name() {
		
	}
	
	/**
	 * A random top level domain.
	 */
	public function tld() {
		$domain = array_rand($this->top_level_domains);
		return $this->top_level_domains[$domain];
	}
	
	/**
	 * A random domain suffix.
	 */
	public function domain_suffix() {
		$domain = array_rand($this->domain_suffixes);
		return $this->domain_suffixes[$domain];
	}

	/**
	 * A random network service name. Only fairly common services are included.
	 */
	public function network_service() {
		return $this->lexicalize($this->network_services);
	}

	/**
	 * A random IP V4 Address.
	 */
	public function ip_v4() {
		return implode('.', array(rand(1, 254), rand(1,254), rand(1,254), rand(1,254)));
	}
	
	/**
	 * A random IP V6 Address.
	 */
	public function ip_v6() {
		//
	}
	
	
	/**
	 * A random IP V6 Address.
	 */
	public function yow() {
		$this->lex();
	}

	/**
 	 * A fake email address.
     *
     * @param string $name
	 * @param string $domain
	 */
	public function email($name=false, $domain=false) {
		return 'test@testy.com';
	}
	
	/**
	 * Make a random url
	 */
	public function url() {
		$faker = new Faker();
		return 'http://google.com?q='.urlencode($faker->company->bullshit());
	}

}