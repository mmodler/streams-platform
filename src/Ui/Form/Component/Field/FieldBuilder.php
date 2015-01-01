<?php namespace Anomaly\Streams\Platform\Ui\Form\Component\Field;

use Anomaly\Streams\Platform\Addon\FieldType\FieldTypeBuilder;
use Anomaly\Streams\Platform\Stream\Contract\StreamInterface;
use Anomaly\Streams\Platform\Ui\Form\Component\Field\Guesser\FieldsGuesser;
use Anomaly\Streams\Platform\Ui\Form\FormBuilder;
use Laracasts\Commander\CommanderTrait;

/**
 * Class FieldTypeBuilder
 *
 * @link    http://anomaly.is/streams-platform
 * @author  AnomalyLabs, Inc. <hello@anomaly.is>
 * @author  Ryan Thompson <ryan@anomaly.is>
 * @package Anomaly\Streams\Platform\Ui\Form\Component\Field
 */
class FieldBuilder
{

    use CommanderTrait;

    /**
     * The field reader.
     *
     * @var FieldReader
     */
    protected $reader;

    /**
     * The field type builder.
     *
     * @var FieldTypeBuilder
     */
    protected $builder;

    /**
     * The field factory.
     *
     * @var FieldFactory
     */
    protected $factory;

    /**
     * The fill guesser.
     *
     * @var FieldsGuesser
     */
    protected $fields;

    /**
     * The configurator utility.
     *
     * @var FieldConfigurator
     */
    protected $configurator;

    /**
     * Create a new FieldTypeBuilder instance.
     *
     * @param FieldReader       $reader
     * @param FieldFactory      $factory
     * @param FieldsGuesser     $fields
     * @param FieldConfigurator $configurator
     */
    public function __construct(
        FieldReader $reader,
        FieldFactory $factory,
        FieldsGuesser $fields,
        FieldConfigurator $configurator
    ) {
        $this->reader       = $reader;
        $this->factory      = $factory;
        $this->fields       = $fields;
        $this->configurator = $configurator;
    }

    /**
     * Build the fields.
     *
     * @param FormBuilder $builder
     */
    public function build(FormBuilder $builder)
    {
        $form   = $builder->getForm();
        $fields = $form->getFields();
        $stream = $form->getStream();
        $entry  = $form->getEntry();

        $options  = $form->getOptions();
        $excluded = $options->get('exclude', []);

        $configuration = $builder->getFields();

        /**
         * Start by standardizing the input.
         */
        foreach ($configuration as $slug => &$parameters) {
            $parameters = $this->reader->standardize($slug, $parameters);
        }

        /**
         * Guess the filler fields for "*".
         */
        if ($stream instanceof StreamInterface) {
            $configuration = $this->fields->guess($stream, $configuration);
        }

        /**
         * Convert each field to a field object
         * and put to the forms field collection.
         */
        foreach ($configuration as $slug => $parameters) {

            // Standardize the input.
            $parameters = $this->reader->standardize($slug, $parameters);

            // Skip excluded fields.
            if (in_array($parameters['field'], $excluded)) {
                continue;
            }

            // Make the field object.
            $field = $this->factory->make($parameters, $stream, $entry);

            // Configure the overrides using the configurator.
            $this->configurator->configure($field, $parameters);

            // Load it up onto the table's fields.
            $fields->put($field->getField(), $field);
        }
    }
}
