<?php

namespace App\Enums;

enum CompanyApprovalAction: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
