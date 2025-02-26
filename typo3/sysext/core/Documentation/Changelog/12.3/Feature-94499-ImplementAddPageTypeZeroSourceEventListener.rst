.. include:: /Includes.rst.txt

.. _feature-94499-1675615684:

================================================================
Feature: #94499 - Implement AddPageTypeZeroSource Event Listener
================================================================

See :issue:`94499`

Description
===========

A new event listener for :ref:`\TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent <feature-99746-1675059434>`
is introduced, which creates a :ref:`\TYPO3\CMS\Redirects\RedirectUpdate\PageTypeSource <feature-94499-1675615570>` for page
before slug has been changed. The full URI is built to fill the `source_host`
and `source_path`, which takes configured `RouteEnhancers` and `RouteDecorators`
into account, e.g. the `PageType route decorator`.

..  note::

    If `source_host` and `source_path` leads to the same outcome for page type 0
    using the full URI building like the :php:`\TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementSource`, the
    :php:`PlainSlugReplacementSource` is replaced with the :php:`PageTypeSource`.

It's not possible to configure for which page types sources should be added. If
you need to do so, read :ref:`additional `PageTypeSource` auto-create redirect source type <feature-94499-1675615570>`
which contains an example how to implement custom event listener based on
:php:`PageTypeSource`.

In the case that :php:`PageTypeSource` for page type `0` results in a different
source, the :php:`PlainSlugReplacementSource` is not removed to keep the original
behaviour, which some instances may rely on.

This behaviour can be modified by adding an event listener for
:ref:`SlugRedirectChangeItemCreatedEvent <feature-99746-1675059434>`

Remove plain slug source if page type 0 differs:
------------------------------------------------

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_package/Configuration/Services.yaml

    MyVendor\MyPackage\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/custom-page-type-redirect'
          # Registering after core listener is important, otherwise we would
          # not know if there is a PageType source for page type 0
          after: 'redirects-add-page-type-zero-source'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_package/Classes/Backend/MyEventListener.php

    namespace MyVendor\MyPackage\Backend;

    use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
    use TYPO3\CMS\Redirects\RedirectUpdate\PageTypeSource;
    use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
    use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
    use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface;

    final class MyEventListener
    {
        public function __invoke(
            SlugRedirectChangeItemCreatedEvent $event
        ): void {
            $changeItem = $event->getSlugRedirectChangeItem();
            $sources = $changeItem->getSourcesCollection()->all();
            $pageTypeZeroSource = $this->getPageTypeZeroSource(
                ...array_values($sources)
            );
            if ($pageTypeZeroSource === null) {
                // nothing we can do - no page type 0 source found
                return;
            }

            // Remove plain slug replacement redirect source from sources. We
            // already know, that if it is there it differs from the page type
            // 0 source, therefor it is safe to simply remove it by class check.
            $sources = array_filter(
                $sources,
                static fn ($source) => !($source instanceof PlainSlugReplacementRedirectSource)
            );

            // update sources
            $changeItem = $changeItem->withSourcesCollection(
                new RedirectSourceCollection(
                    ...array_values($sources)
                )
            );

            // update change item with updated sources
            $event->setSlugRedirectChangeItem($changeItem);
        }

        private function getPageTypeZeroSource(
            RedirectSourceInterface ...$sources
        ): ?PageTypeSource {
            foreach ($sources as $source) {
                if ($source instanceof PageTypeSource
                    && $source->getPageType() === 0
                ) {
                   return $source;
                }
            }
            return null;
        }
    }

Impact
======

Additional redirect source is automatically added, e.g. if a `PageType suffix`
is configured in the :php:`SiteConfiguration` for page type `0`. In that case
two redirects are created, one for the plain slug change and one with the suffix
in the `source_path`. That way it's not breaking for instances relying on the
fact that the plain slug based redirects are created.

..  note::

    This behaviour can be modified by adding an event listener for
    :ref:`SlugRedirectChangeItemCreatedEvent <feature-99746-1675059434>`,
    checking if both variants are in the source collection and remove the
    :php:`PlainSlugReplacementSource`, as found in the example above.

..  todo::

    Add link to main documentation or EXT:redirects once this contains more examples for the new events.
    The documentation will later be modified to include more examples.

.. index:: PHP-API, ext:redirects
