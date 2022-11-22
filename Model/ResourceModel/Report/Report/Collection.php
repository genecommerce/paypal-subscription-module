<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Model\ResourceModel\Report\Report;

use DateTime;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\ResourceModel\Report;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

/**
 * @api
 */
class Collection extends Report\Collection\AbstractCollection
{
    /**
     * Selected columns
     *
     * @var array
     */
    private $selectedColumns = [];

    /**
     * Tables per period
     *
     * @var array
     */
    protected $tableForPeriod = [
        'daily'   => 'paypal_subs_report_aggregated_daily',
        'monthly' => 'paypal_subs_report_aggregated_monthly',
        'yearly'  => 'paypal_subs_report_aggregated_yearly',
    ];

    /**
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param Report $resource
     * @param AdapterInterface $connection
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Report $resource,
        AdapterInterface $connection = null
    ) {
        $resource->init($this->getTableByAggregationPeriod('daily'));
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $resource, $connection);
    }

    /**
     * Return ordered filed
     *
     * @return string
     */
    protected function getOrderedField()
    {
        return 'num_subscriptions';
    }

    /**
     * Return table per period
     *
     * @param string $period
     * @return mixed
     */
    public function getTableByAggregationPeriod(string $period): mixed
    {
        return $this->tableForPeriod[$period];
    }

    /**
     * Retrieve selected columns
     *
     * @return array
     */
    protected function _getSelectedColumns(): array
    {
        $connection = $this->getConnection();

        if (!$this->selectedColumns) {
            if ($this->isTotals()) {
                $this->selectedColumns = $this->getAggregatedColumns();
            } else {
                $this->selectedColumns = [
                    'period' => sprintf('MAX(%s)', $connection->getDateFormatSql('period', '%Y-%m-%d')),
                    $this->getOrderedField() => 'SUM(' . $this->getOrderedField() . ')',
                    'product_id' => 'product_id',
                    'product_sku' => 'MAX(product_sku)',
                    'product_name' => 'MAX(product_name)'
                ];
                if ('year' === $this->_period) {
                    $this->selectedColumns['period'] = $connection->getDateFormatSql('period', '%Y');
                } elseif ('month' === $this->_period) {
                    $this->selectedColumns['period'] = $connection->getDateFormatSql('period', '%Y-%m');
                }
            }
        }
        return $this->selectedColumns;
    }

    /**
     * Make select object for date boundary
     *
     * @param string $from
     * @param string $to
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _makeBoundarySelect($from, $to): Select
    {
        $connection = $this->getConnection();
        $cols = $this->_getSelectedColumns();
        $cols[$this->getOrderedField()] = 'SUM(' . $this->getOrderedField() . ')';
        $select = $connection->select()->from(
            $this->getResource()->getMainTable(),
            $cols
        )->where(
            'period >= ?',
            $from
        )->where(
            'period <= ?',
            $to
        )->group(
            'product_id'
        )->order(
            $this->getOrderedField() . ' DESC'
        );

        $this->_applyStoresFilterToSelect($select);
        return $select;
    }

    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _applyAggregatedTable(): self
    {
        $select = $this->getSelect();

        //if grouping by product, not by period
        if (!$this->_period) {
            $cols = $this->_getSelectedColumns();
            $cols[$this->getOrderedField()] = 'SUM(' . $this->getOrderedField() . ')';
            if ($this->_from || $this->_to) {
                $mainTable = $this->getTable($this->getTableByAggregationPeriod('daily'));
                $select->from($mainTable, $cols);
            } else {
                $mainTable = $this->getTable($this->getTableByAggregationPeriod('yearly'));
                $select->from($mainTable, $cols);
            }

            //exclude removed products
            $select->where(new Zend_Db_Expr($mainTable . '.product_id IS NOT NULL'))->group(
                'product_id'
            )->order(
                $this->getOrderedField() . ' ' . Select::SQL_DESC
            );

            return $this;
        }

        if ('year' === $this->_period) {
            $mainTable = $this->getTable($this->getTableByAggregationPeriod('yearly'));
            $select->from($mainTable, $this->_getSelectedColumns());
        } elseif ('month' === $this->_period) {
            $mainTable = $this->getTable($this->getTableByAggregationPeriod('monthly'));
            $select->from($mainTable, $this->_getSelectedColumns());
        } else {
            $mainTable = $this->getTable($this->getTableByAggregationPeriod('daily'));
            $select->from($mainTable, $this->_getSelectedColumns());
        }
        if (!$this->isTotals()) {
            $select->group(['period', 'product_id']);
        }

        return $this;
    }

    /**
     * Get SQL for get record count
     *
     * @return Select
     */
    public function getSelectCountSql(): Select
    {
        $this->_renderFilters();
        $select = clone $this->getSelect();
        $select->reset(Select::ORDER);
        return $this->getConnection()->select()->from($select, 'COUNT(*)');
    }

    /**
     * Set ids for store restrictions
     *
     * @param  int|int[] $storeIds
     * @return $this
     */
    public function addStoreRestrictions($storeIds): self
    {
        if (!is_array($storeIds)) {
            $storeIds = [$storeIds];
        }
        $currentStoreIds = $this->_storesIds;
        if (isset(
            $currentStoreIds
        ) && $currentStoreIds !== Store::DEFAULT_STORE_ID && $currentStoreIds !== [
            Store::DEFAULT_STORE_ID
        ]
        ) {
            if (!is_array($currentStoreIds)) {
                $currentStoreIds = [$currentStoreIds];
            }
            $this->_storesIds = array_intersect($currentStoreIds, $storeIds);
        } else {
            $this->_storesIds = $storeIds;
        }

        return $this;
    }

    /**
     * Redeclare parent method for applying filters after parent method
     * but before adding unions and calculating totals
     *
     * @return $this|AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();

        $this->_applyStoresFilter();

        if ($this->_period) {
            $selectUnions = [];

            $periodFrom = ($this->_from !== null) ? new DateTime($this->_from) : null;
            $periodTo = ($this->_to !== null) ? new DateTime($this->_to) : null;
            if ('year' === $this->_period) {
                if ($periodFrom) {
                    // not the first day of the year
                    if ($periodFrom->format('m') !== 1 || $periodFrom->format('d') !== 1) {
                        $dtFrom = clone $periodFrom;
                        // last day of the year
                        $dtTo = clone $periodFrom;
                        $dtTo->setDate((int) $dtTo->format('Y'), 12, 31);
                        if (!$periodTo || $dtTo < $periodTo) {
                            $selectUnions[] = $this->_makeBoundarySelect(
                                $dtFrom->format('Y-m-d'),
                                $dtTo->format('Y-m-d')
                            );

                            // first day of the next year
                            $this->_from = clone $periodFrom;
                            $this->_from->modify('+1 year');
                            $this->_from->setDate((int) $this->_from->format('Y'), 1, 1);
                            $this->_from = $this->_from->format('Y-m-d');
                        }
                    }
                }

                if ($periodTo) {
                    // not the last day of the year
                    if ($periodTo->format('m') != 12 || $periodTo->format('d') != 31) {
                        $dtFrom = clone $periodTo;
                        $dtFrom->setDate((int) $dtFrom->format('Y'), 1, 1);
                        // first day of the year
                        $dtTo = clone $periodTo;
                        if (!$periodFrom || $dtFrom > $periodFrom) {
                            $selectUnions[] = $this->_makeBoundarySelect(
                                $dtFrom->format('Y-m-d'),
                                $dtTo->format('Y-m-d')
                            );

                            // last day of the previous year
                            $this->_to = clone $periodTo;
                            $this->_to->modify('-1 year');
                            $this->_to->setDate((int) $this->_to->format('Y'), 12, 31);
                            $this->_to = $this->_to->format('Y-m-d');
                        }
                    }
                }

                // the same year
                if ($periodFrom && $periodTo && $periodTo->format('Y') === $periodFrom->format('Y')) {
                    $dtFrom = clone $periodFrom;
                    $dtTo = clone $periodTo;
                    $selectUnions[] = $this->_makeBoundarySelect(
                        $dtFrom->format('Y-m-d'),
                        $dtTo->format('Y-m-d')
                    );

                    $this->getSelect()->where('1<>1');
                }
            } elseif ('month' === $this->_period) {
                // not the first day of the month
                if ($periodFrom && $periodFrom->format('d') !== 1) {
                    $dtFrom = clone $periodFrom;
                    // last day of the month
                    $dtTo = clone $periodFrom;
                    $dtTo->modify('+1 month');
                    $dtTo->setDate((int) $dtTo->format('Y'), (int) $dtTo->format('m'), 1);
                    $dtTo->modify('-1 day');
                    if (!$periodTo || $dtTo < $periodTo) {
                        $selectUnions[] = $this->_makeBoundarySelect(
                            $dtFrom->format('Y-m-d'),
                            $dtTo->format('Y-m-d')
                        );

                        // first day of the next month
                        $this->_from = clone $periodFrom;
                        $this->_from->modify('+1 month');
                        $this->_from->setDate((int) $this->_from->format('Y'), (int) $this->_from->format('m'), 1);
                        $this->_from = $this->_from->format('Y-m-d');
                    }
                }

                // not the last day of the month
                if ($periodTo && $periodTo->format('d') !== $periodTo->format('t')) {
                    $dtFrom = clone $periodTo;
                    $dtFrom->setDate((int) $dtFrom->format('Y'), (int) $dtFrom->format('m'), 1);
                    // first day of the month
                    $dtTo = clone $periodTo;
                    if (!$periodFrom || $dtFrom > $periodFrom) {
                        $selectUnions[] = $this->_makeBoundarySelect(
                            $dtFrom->format('Y-m-d'),
                            $dtTo->format('Y-m-d')
                        );

                        // last day of the previous month
                        $this->_to = clone $periodTo;
                        $this->_to->setDate((int) $this->_to->format('Y'), (int) $this->_to->format('m'), 1);
                        $this->_to->modify('-1 day');
                        $this->_to = $this->_to->format('Y-m-d');
                    }
                }

                // the same month
                if ($periodFrom && $periodTo && $periodTo->format('Y') === $periodFrom->format('Y') &&
                    $periodTo->format('m') === $periodFrom->format('m')) {
                        $dtFrom = clone $periodFrom;
                        $dtTo = clone $periodTo;
                        $selectUnions[] = $this->_makeBoundarySelect(
                            $dtFrom->format('Y-m-d'),
                            $dtTo->format('Y-m-d')
                        );

                        $this->getSelect()->where('1<>1');
                }
            }

            $this->_applyDateRangeFilter();

            // add unions to select
            if ($selectUnions) {
                $unionParts = [];
                $cloneSelect = clone $this->getSelect();
                $unionParts[] = '(' . $cloneSelect . ')';
                foreach ($selectUnions as $union) {
                    $unionParts[] = '(' . $union . ')';
                }
                $this->getSelect()->reset()->union($unionParts, Select::SQL_UNION_ALL);
            }

            if ($this->isTotals()) {
                // calculate total
                $cloneSelect = clone $this->getSelect();
                $this->getSelect()->reset()->from($cloneSelect, $this->getAggregatedColumns());
            } else {
                // add sorting
                $this->getSelect()->order(['period ASC', $this->getOrderedField() . ' DESC']);
            }
        }

        return $this;
    }
}
