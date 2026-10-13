<?php

namespace App\Enums;

enum TaskStatus: string
{
    case ToDo = 'to do';
    case InProgress = 'in progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::ToDo => 'To do',
            self::InProgress => 'In progress',
            self::Done => 'Done',
        };
    }
}
