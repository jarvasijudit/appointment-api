<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum Specialization: string
{
    use HasValues;

    case GeneralPractice = 'general_practice';
    case InternalMedicine = 'internal_medicine';
    case Cardiology = 'cardiology';
    case Neurology = 'neurology';
    case Orthopedics = 'orthopedics';
    case Dermatology = 'dermatology';
    case Psychiatry = 'psychiatry';
    case Pediatrics = 'pediatrics';
    case Gynecology = 'gynecology';
    case Urology = 'urology';
    case Ophthalmology = 'ophthalmology';
    case Radiology = 'radiology';
    case Surgery = 'surgery';
    case Oncology = 'oncology';
    case Gastroenterology = 'gastroenterology';
    case Pulmonology = 'pulmonology';
    case Endocrinology = 'endocrinology';
    case Rheumatology = 'rheumatology';
    case Nephrology = 'nephrology';
    case Hematology = 'hematology';
}
