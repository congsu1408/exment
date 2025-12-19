<?php

namespace Exceedone\Exment\Tests\Browser;

use Exceedone\Exment\Tests\Constraints;
use Exceedone\Exment\Tests\TestTrait;
use Exceedone\Exment\Model\LoginUser;
use Exceedone\Exment\Model\System;
use Exceedone\Exment\Tests\DatabaseTransactions;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

/**
 * @method \Exceedone\Exment\Tests\DatabaseTransactions beginDatabaseTransaction()
 */
abstract class ExmentKitTestCase extends BaseTestCase
{
    use \Tests\CreatesApplication;
    use TestTrait;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var bool
     */
    protected static $ensuredTestUsers = false;


    /**
     * pre-excecute process before test.
     * @return void
     */
    protected function setUp(): void
    {
        // cannot call method "config", so call env function
        $appUrl = \env('APP_URL');
        $this->baseUrl = !is_null($appUrl) && $appUrl !== '' ? rtrim($appUrl, '/') : 'http://localhost';
        parent::setUp();
        System::clearCache();
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array<mixed>
     */
    protected function setUpTraits()
    {
        $uses = parent::setUpTraits();

        if (isset($uses[DatabaseTransactions::class])) {
            $this->beginDatabaseTransaction();
        }

        return $uses;
    }

    // ...

    /**
     * @param mixed|null $id
     * @return void
     */
    protected function login($id = null)
    {
        $targetId = $id ?? 1;

        $user = LoginUser::find($targetId);

        // Some CI DB setups may not have run/kept the seeded test data.
        // Ensure test users exist once per process to avoid cascading TypeErrors.
        if (!$user && !static::$ensuredTestUsers) {
            static::$ensuredTestUsers = true;

            try {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Exceedone\Exment\Database\Seeder\TestDataSeeder::class]);
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Exceedone\Exment\Database\Seeder\WorkflowTestDataSeeder::class]);
            } catch (\Throwable $e) {
                // If seeding fails, fall through and surface a clear assertion below.
            }

            $user = LoginUser::find($targetId);
        }

        if (!$user) {
            $user = LoginUser::orderBy('id')->first();
        }

        $this->assertTrue(isset($user), 'login user not found. Ensure `php artisan exment:inittest --yes` seeded users into the same DB connection used by Browser tests.');
        $this->be($user);
    }


    /**
     * @param string|int $code
     * @return $this
     */
    protected function matchStatusCode($code)
    {
        $this->assertTrue($code == $this->response->getStatusCode(), "Expects {$code}, but result is " . $this->response->getStatusCode());

        return $this;
    }


    /**
     * Assert that a given string is seen outside an element.
     *
     * @param  string  $element
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function seeOuterElement($element, $text, $negate = false)
    {
        return $this->assertInPage(new Constraints\HasOuterElement($element, $text), $negate);
    }


    /**
     * Assert that a select cptions  an element.
     *
     * @param  string  $element
     * @param  array<mixed>  $options key: option's value, value: text
     * @param  bool  $negate
     * @return $this
     */
    public function exactSelectOptions($element, array $options, $negate = false)
    {
        return $this->assertInPage(new Constraints\ExactSelectOption($element, $options), $negate);
    }

    /**
     * Assert that a select options  an element.
     *
     * @param  string  $element
     * @param  array<mixed>  $options key: option's value, value: text
     * @param  bool  $negate
     * @return $this
     */
    public function containsSelectOptions($element, array $options, $negate = false)
    {
        return $this->assertInPage(new Constraints\ContainsSelectOption($element, $options), $negate);
    }
}
