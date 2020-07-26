<?php


namespace Ecotone\Amqp\AmqpTransaction;


use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\PollableEndpoint;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\LazyEventBus\LazyEventBusInterceptor;
use Enqueue\AmqpLib\AmqpConnectionFactory;

/**
 * @ModuleAnnotation()
 */
class AmqpTransactionConfiguration implements AnnotationModule
{
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "amqpTransactionModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $connectionFactories = [AmqpConnectionFactory::class];
        $pointcut = "@(" . AmqpTransaction::class . ")";
        $amqpConfiguration = AmqpConfiguration::createWithDefaults();
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof AmqpConfiguration) {
                $amqpConfiguration = $extensionObject;
            }
        }

        if ($amqpConfiguration->isDefaultTransactionOnAsynchronousEndpoints()) {
            $pointcut .= "||@(" . AsynchronousRunningEndpoint::class . ")";
        }
        if ($amqpConfiguration->isDefaultTransactionOnCommandBus()) {
            $pointcut .= "||" . CommandBus::class . "";
        }
        if ($amqpConfiguration->getDefaultConnectionReferenceNames()) {
            $connectionFactories = $amqpConfiguration->getDefaultConnectionReferenceNames();
        }

        $configuration
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithObjectBuilder(
                    AmqpTransactionInterceptor::class,
                    new AmqpTransactionInterceptorBuilder($connectionFactories),
                    "transactional",
                    Precedence::DATABASE_TRANSACTION_PRECEDENCE - 1,
                    $pointcut
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof AmqpConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}