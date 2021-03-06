<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\session\adapter;

use lithium\util\Inflector;
use lithium\storage\session\adapter\Cookie;

class CookieTest extends \lithium\test\Unit {

	/**
	 * Skip the test if running under CLI.
	 *
	 * @return void
	 */
	public function skip() {
		$sapi = PHP_SAPI;
		$message = 'Cookie tests cannot be run via command-line interface.';
		$this->skipIf($sapi === 'cli', $message);
	}

	public function setUp() {
		$this->cookie = new Cookie();
		$path = explode('/', LITHIUM_APP_PATH);
		$this->name = end($path) . 'cookie';
	}

	public function tearDown() {
		$this->_destroyCookie();
	}

	protected function _destroyCookie($name = null) {
		if (!$name) {
			$name = session_name();
		}
		$settings = session_get_cookie_params();
		setcookie(
			$name, '', time() - 1000, $settings['path'], $settings['domain'],
			$settings['secure'], $settings['httponly']
		);
		if (session_id()) {
			session_destroy();
		}
		$_COOKIE = array();
	}

	public function testEnabled() {
		$this->assertTrue($this->cookie->isEnabled());
	}

	public function testKey() {
		$this->assertEqual($this->name, $this->cookie->key());
	}

	public function testIsStarted() {
		$this->assertTrue($this->cookie->isStarted());
	}

	public function testWriteDefaultParameters() {
		$key = 'write';
		$value = 'value to be written';
		$expires = "+2 days";
		$path = '/';

		$closure = $this->cookie->write($key, $value);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'value');
		$result = $closure($this->cookie, $params, null);

		$this->assertCookie(compact('key', 'value', 'expires', 'path'));
	}

	public function testCustomCookieName() {
		$Cookie = new Cookie(array('name' => 'test'));
		$this->assertEqual('test', $Cookie->key());
	}


	public function testWriteArrayData() {
		$key = 'user';
		$value = array('email' => 'test@localhost', 'name' => 'Testy McTesterson');
		$expires = "+2 days";
		$path = '/';

		$closure = $this->cookie->write($key, $value);
		$this->assertTrue(is_callable($closure));
		$params = compact('key', 'value');
		$result = $closure($this->cookie, $params, null);

		$expected = compact('expires');
		$expected += array('key' => 'user.email', 'value' => 'test@localhost');
		$this->assertCookie($expected, headers_list());
	}

	public function testReadDotSyntax() {
		$key = 'read.test';
		$value = 'value to be read';
		$_COOKIE[$this->name]['read']['test'] = $value;

		$closure = $this->cookie->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertEqual($value, $result);

		$result = $closure($this->cookie, array('key' => null), null);
		$this->assertEqual($_COOKIE, $result);

		$key = 'does_not_exist';
		$closure = $this->cookie->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertNull($result);

	}

	public function testWriteCustomParameters() {
		$key = 'write';
		$value = 'value to be written';
		$expires = "+1 day";
		$path = '/';
		$options = array('expire' => $expires);

		$closure = $this->cookie->write($key, $value, $options);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'value', 'options');
		$result = $closure($this->cookie, $params, null);

		$this->assertCookie(compact('key', 'value', 'expires', 'path'));
	}

	public function testRead() {
		$key = 'read';
		$value = 'value to be read';
		$_COOKIE[$this->name][$key] = $value;

		$closure = $this->cookie->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertEqual($value, $result);

		$result = $closure($this->cookie, array('key' => null), null);
		$this->assertEqual($_COOKIE, $result);

		$key = 'does_not_exist';
		$closure = $this->cookie->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertNull($result);
	}

	public function testCheck() {
		$key = 'read';
		$value = 'value to be read';
		$_COOKIE[$this->name][$key] = $value;

		$closure = $this->cookie->check($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertTrue($result);

		$key = 'does_not_exist';
		$closure = $this->cookie->check($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertFalse($result);
	}

	public function testDeleteNonExistentValue() {
		$key = 'delete';
		$value = 'deleted';
		$path = '/';

		$closure = $this->cookie->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->cookie, $params, null);
		$this->assertNull($result);
		$this->assertCookie(compact('key', 'value', 'path'));
	}

	public function testDefaultCookieName() {
		$cookie = new Cookie();
		$expected = Inflector::slug(basename(LITHIUM_APP_PATH)) . 'cookie';
		$this->assertEqual($expected, $cookie->key());
	}

	public function testBadWrite() {
		$cookie = new Cookie(array('expire' => null));
		$this->assertNull($cookie->write('bad', 'val'));
	}

	public function testNameWithDotCookie() {
		$cookie = new Cookie(array('name' => 'my.name'));
		$key = 'key';
		$value = 'value';
		$result = $cookie->write($key, $value)->__invoke($cookie, compact('key', 'value'), null);
		$this->assertCookie(compact('key', 'value'));
	}
}

?>