<?php

namespace Intersect\Database\Model;

use Intersect\Database\Exception\OperationNotSupportedException;

abstract class ReadOnlyModel extends Model {

    /**
     * @return mixed|null|void
     * @throws OperationNotSupportedException
     */
    public function save()
    {
        throw new OperationNotSupportedException(get_class($this) . ' is readonly! Cannot perform save/update operations.');
    }

    /**
     * @return bool|void
     * @throws OperationNotSupportedException
     */
    public function delete()
    {
        throw new OperationNotSupportedException(get_class($this) . ' is readonly! Cannot perform delete operations.');
    }

}