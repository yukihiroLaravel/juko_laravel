<?php

namespace App\Dto\Student\Notification;

class IndexDto
{
    /**
     * @param  string|null  $searchWord
     */
    public function __construct(
        private readonly int $studentId,
        private readonly int $perPage,
        private readonly int $page,
        private readonly string $sortBy,
        private readonly string $order,
        private readonly string $filter
    ) {}

    // 全体取得
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

    // ユーザIDを取得
    public function getStudentId(): int
    {
        return $this->studentId;
    }

    // 1ページあたりの件数を取得
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    // ページ番号を取得
    public function getPage(): int
    {
        return $this->page;
    }

    // ソート対象カラムを取得
    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    // ソート順を取得
    public function getOrder(): string
    {
        return $this->order;
    }

    // 既読・未読フィルタを取得
    public function getFilter(): string
    {
        return $this->filter;
    }
}
