<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Exception\ServerException;

/**
 * Limited pipeline model.
 */
class LimitedPipelineModel extends PipelineModel {
    /** @var array $operations List of accepted db operations */
    protected $operations = [];

    /**
     * @inheritdoc
     */
    public function get(array $where = [], array $options = []): array {
        if (in_array('get', $this->operations)) {
            return parent::get($where, $options);
        } else {
            throw new ServerException('Method get() is not supported.');
        }
    }

    /**
     * @inheritdoc
     */
    public function insert(array $set) {
        if (in_array('insert', $this->operations)) {
            return parent::insert($set);
        } else {
            throw new ServerException('Method insert() is not supported.');
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(array $where, array $options = []): bool {
        if (in_array('delete', $this->operations)) {
            return parent::delete($where, $options);
        } else {
            throw new ServerException('Method delete() is not supported.');
        }
    }

    /**
     * @inheritdoc
     */
    public function selectSingle(array $where = [], array $options = []): array {
        if (in_array('selectSingle', $this->operations)) {
            return parent::selectSingle($where, $options);
        } else {
            throw new ServerException('Method selectSingle() is not supported.');
        }
    }

    /**
     * @inheritdoc
     */
    public function update(array $set, array $where): bool {
        if (in_array('update', $this->operations)) {
            return parent::update($set, $where);
        } else {
            throw new ServerException('Method update() is not supported.');
        }
    }
}
