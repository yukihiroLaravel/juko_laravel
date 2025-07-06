<?php

namespace App\Dto\Student\Notification;

class IndexDto
{
    /**
     * お知らせ一覧取得用DTO
     *
     * 学生IDやページ情報、ソート・フィルタ条件をまとめて渡す
     */
    public function __construct(
        public readonly int $studentId,
        public readonly int $perPage,
        public readonly int $page,
        public readonly string $sortBy,
        public readonly string $order,
        public readonly string $filter
    ) {}

    public function getIndex(): array
    {
        return [
            'student_id' => $this->studentId,
            'per_page'   => $this->perPage,
            'page'       => $this->page,
            'sort_by'    => $this->sortBy,
            'order'      => $this->order,
            'filter'     => $this->filter,
        ];
    }
}
