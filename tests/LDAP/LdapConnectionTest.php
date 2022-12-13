<?php

namespace App\Tests\LDAP;

use App\dataType\LdapType;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LdapConnectionTest extends KernelTestCase
{
    public static $UserInLDAP = 4;
    public static $USERWITHLDAPUSERPROPERTIES = 4 + 1;
    public static $UserInSubLDAP = 2;
    public static $UserInOneLDAP = 2;
    public $LDAPURL = 'ldap://192.168.230.128:10389';

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $para = self::getContainer()->get(ParameterBagInterface::class);

        $this->LDAPURL = $para->get('ldap_test_url');
    }

    public function testConnectionOhneLogin(): void
    {

        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('');
        $ldapConnection->setPassword('');
        $ldapConnection->setBindType('none');
        $ldap = $ldapConnection->createLDAP();
        $this->assertEquals(self::$UserInLDAP, $ldapConnection->getLdap()->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user))(&(mail=*)))', array('scope' => 'sub'))->execute()->count());
    }

    public function testConnectionMitLogin(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result

        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $this->assertEquals(self::$UserInLDAP, $ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user))(&(mail=*)))', array('scope' => 'sub'))->execute()->count());
    }

    public function testConnectionMitLoginOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $this->assertEquals(self::$UserInOneLDAP, $ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user))(&(mail=*)))', array('scope' => 'one'))->execute()->count());
    }

    public function testcreateObjectClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $ldapConnection->setFilter('(&(mail=*))');
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $this->assertEquals('(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user))(&(mail=*)))', $ldapConnection->buildObjectClass());
        $ldapConnection->setFilter(null);
        $this->assertEquals('(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))', $ldapConnection->buildObjectClass());
    }

    public function testcreateObjectClassDeputy(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->setLDAPDEPUTYGROUPFILTER('(&(memberOf=deputy-groups))');
        $ldapConnection->setLDAPDEPUTYGROUPOBJECTCLASS('posixGroups,groups');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $this->assertEquals('(&(|(objectclass=posixGroups)(objectclass=groups))(&(memberOf=deputy-groups)))', $ldapConnection->buildObjectClassDeputy());
        $ldapConnection->setLDAPDEPUTYGROUPFILTER(null);
        $this->assertEquals('(|(objectclass=posixGroups)(objectclass=groups))', $ldapConnection->buildObjectClassDeputy());
    }


    public function testcreateFetchUserOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();

        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setSerVerId('Server1');
        $ldapConnection->setPassword('password');
        $ldapConnection->setScope('one');
        $ldapConnection->setMapper(array("firstName" => "givenName", "lastName" => "sn", "email" => "uid"));
        $ldapConnection->setSpecialFields(array("ou" => "ou", "departmentNumber" => "departmentNumber"));
        $ldapConnection->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapConnection->setBindType('none');
        $ldapConnection->setRdn('uid');
        $ldapConnection->setLdap($ldap);
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $ldapConnection->setUserNameAttribute('uid');
        $ldapConnection->setFilter('(&(mail=*))');
        $this->assertEquals(2, sizeof($ldapService->fetchLdap($ldapConnection)['user']));

    }

    public function testcreateFetchUserSub(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();

        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setSerVerId('Server1');
        $ldapConnection->setPassword('password');
        $ldapConnection->setScope('sub');
        $ldapConnection->setMapper(array("firstName" => "givenName", "lastName" => "sn", "email" => "uid"));
        $ldapConnection->setSpecialFields(array("ou" => "ou", "departmentNumber" => "departmentNumber"));
        $ldapConnection->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapConnection->setBindType('none');
        $ldapConnection->setRdn('uid');
        $ldapConnection->setLdap($ldap);
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $ldapConnection->setUserNameAttribute('uid');
        $ldapConnection->setFilter('(&(mail=*))');
        $this->assertEquals(self::$UserInLDAP, sizeof($ldapService->fetchLdap($ldapConnection)['user']));
    }

    public function testRetrieveUserOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->createLDAP();
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $ldapConnection->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapConnection->setScope('one');
        $ldapConnection->setFilter('(&(mail=*))');
        $this->assertEquals(self::$UserInOneLDAP, sizeof($ldapConnection->retrieveUser()));
    }

    public function testRetrieveUserSub(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->createLDAP();
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $ldapConnection->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapConnection->setScope('sub');
        $ldapConnection->setFilter('(&(mail=*))');
        $this->assertEquals(self::$UserInLDAP, sizeof($ldapConnection->retrieveUser()));
    }

    public function testTryToConnect(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapService->setLdaps(array($ldapConnection));
        self::assertEquals(true,$ldapService->connectToLdap());
        self::assertTrue($ldapConnection->isHealthy());
    }

    public function testTryToConnectFail(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL.'failure');
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapService->setLdaps(array($ldapConnection));
        self::assertEquals(false,$ldapService->connectToLdap());
        self::assertFalse($ldapConnection->isHealthy());
    }

    public function testLdapGenearteConfig(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        self::assertEquals(true,$ldapService->setConfig());


    }

    public function testCreateLdapConnection(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        self::assertEquals(true,$ldapService->setConfig());
        self::assertEquals(2,$ldapService->createLdapConnections());
        self::assertEquals(2,sizeof($ldapService->getLdaps()));
    }

    public function testinitLDAP(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapService = self::getContainer()->get(LdapService::class);
        self::assertEquals(true,$ldapService->initLdap());
        self::assertEquals(2,$ldapService->createLdapConnections());
        self::assertEquals(2,sizeof($ldapService->getLdaps()));
    }

}
