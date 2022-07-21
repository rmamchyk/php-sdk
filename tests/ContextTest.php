<?php

use PHPUnit\Framework\TestCase;

use App\EvolvContext;
require_once __DIR__ . '/../App/EvolvContext.php';


class ContextTest extends TestCase {
    protected EvolvContext $context;

    public function setUp(): void {
        $this->context = new EvolvContext();
        $this->context->initialize('user_id');
    }

    /**
     * @group context_set
     */
    public function testNewKeyIsAddedToLocalContext() {
        // Act
        $this->context->set('native.newUser', true, true);

        // Assert
        $this->assertEquals($this->context->localContext, ['native' => ['newUser' => true]]);
    }

    /**
     * @group context_set
     */
    public function testNewKeyIsAddedToRemoteContext() {
        // Act
        $this->context->set('native.newUser', true);

        // Assert
        $this->assertEquals($this->context->remoteContext, ['native' => ['newUser' => true]]);
    }

    /**
     * @group context_remove
     */
    public function testKeyIsRemovedFromLocalContext() {
        // Arrange
        $this->context->set('native', ['newUser' => true, 'pdp' => true], true);

        // Act
        $removed = $this->context->remove('native.newUser');

        // Assert
        $this->assertTrue($removed);
        $this->assertEquals($this->context->localContext, ['native' => ['pdp' => true]]);
    }

    /**
     * @group context_remove
     */
    public function testKeyIsRemovedFromRemoteContext() {
        // Act
        $this->context->set('native', ['newUser' => true, 'pdp' => true]);

        // Act
        $removed = $this->context->remove('native.newUser');

        // Assert
        $this->assertTrue($removed);
        $this->assertEquals($this->context->remoteContext, ['native' => ['pdp' => true]]);
    }

    /**
     * @group context_update
     */
    public function testRemoveContextIsUpdated() {
        // Arrange
        $this->context->set('active.variants', true);

        // Act
        $this->context->update(['active' => ['keys' => true]]);

        // Assert
        $this->assertEquals($this->context->remoteContext, [
            'active' => [
                'variants' => true,
                'keys' => true
            ]
        ]);
    }

    /**
     * @group context_update
     */
    public function testLocalContextIsUpdated() {
        // Arrange
        $this->context->set('active.variants', true, true);

        // Act
        $this->context->update(['active' => ['keys' => true]], true);

        // Assert
        $this->assertEquals($this->context->localContext, [
            'active' => [
                'variants' => true,
                'keys' => true
            ]
        ]);
    }

    /**
     * @group context_pushToArray
     */
    public function testValueIsAddedToSpecifiedArrayInContext() {
        // Arrange
        $this->context->set('my.events', ['event1']);

        // Act
        $this->context->pushToArray('my.events', 'event2');

        // Assert
        $this->assertEquals([
            'my' => [
                'events' => ['event1', 'event2']
            ]
        ], $this->context->remoteContext);
    }
}
