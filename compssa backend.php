<?php

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    // Using filter_input for security and ease of use
    $fullName = filter_input(INPUT_POST, 'fullName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $program = filter_input(INPUT_POST, 'program', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phoneNumber = filter_input(INPUT_POST, 'phoneNumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Consider specific phone validation if needed

    // Retrieve course selections as an array
    $courseSelection = $_POST['courseSelection'] ?? []; // Get array or empty array if not set
    $sanitizedCourses = array_map(function($course) {
        return filter_var($course, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }, $courseSelection);


    $duesPaid = filter_input(INPUT_POST, 'duesPaid', FILTER_VALIDATE_BOOLEAN); // Will be true if 'on', false otherwise

    // Initialize variables for conditional fields
    $admissionYear = null;
    $previousSchool = null;
    $studentId = null;
    $yearOfStudy = null;

    // Determine student type from the presence of specific fields (or hidden field if added)
    // For simplicity, we'll assume 'admissionYear' presence indicates a new student
    // A more robust solution might use a hidden input for student type.
    $studentType = 'unknown';
    if (isset($_POST['admissionYear'])) {
        $studentType = 'new';
        $admissionYear = filter_input(INPUT_POST, 'admissionYear', FILTER_VALIDATE_INT);
        $previousSchool = filter_input(INPUT_POST, 'previousSchool', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    } elseif (isset($_POST['studentId'])) {
        $studentType = 'continuing';
        $studentId = filter_input(INPUT_POST, 'studentId', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $yearOfStudy = filter_input(INPUT_POST, 'yearOfStudy', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    // Server-side validation
    $errors = [];

    if (empty($fullName)) {
        $errors[] = 'Full Name is required.';
    }
    if (empty($program)) {
        $errors[] = 'Program of Study is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid Email Address is required.';
    }
    if (empty($phoneNumber)) {
        $errors[] = 'Phone Number is required.';
    }
    if (empty($sanitizedCourses)) {
        $errors[] = 'At least one course must be selected.';
    }
    if (!$duesPaid) {
        $errors[] = 'Confirmation of dues payment is required.';
    }

    if ($studentType === 'new') {
        if (empty($admissionYear) || $admissionYear === false) { // Check for false from filter_input on validation failure
            $errors[] = 'Admission Year is required and must be a valid number.';
        }
        if (empty($previousSchool)) {
            $errors[] = 'Previous School Attended is required.';
        }
    } elseif ($studentType === 'continuing') {
        if (empty($studentId)) {
            $errors[] = 'Student ID is required.';
        }
        if (empty($yearOfStudy)) {
            $errors[] = 'Year of Study is required.';
        }
    } else {
        $errors[] = 'Invalid student type detected.';
    }


    if (empty($errors)) {
        // All data is valid, proceed with processing (e.g., save to database, send email)

        // Example: Prepare data for storage/processing
        $registrationData = [
            'fullName' => $fullName,
            'program' => $program,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'courses' => $sanitizedCourses,
            'duesPaid' => $duesPaid,
            'studentType' => $studentType
        ];

        if ($studentType === 'new') {
            $registrationData['admissionYear'] = $admissionYear;
            $registrationData['previousSchool'] = $previousSchool;
        } else {
            $registrationData['studentId'] = $studentId;
            $registrationData['yearOfStudy'] = $yearOfStudy;
        }

        // --- Database Insertion Example (requires database connection) ---
        /*
        // Include your database connection file (e.g., db_connect.php)
        require_once 'db_connect.php';

        try {
            $pdo->beginTransaction();

            // Example: Insert into a 'students' table
            $stmt = $pdo->prepare("INSERT INTO students (full_name, program, email, phone_number, student_type, admission_year, previous_school, student_id, year_of_study, dues_paid)
                                 VALUES (:fullName, :program, :email, :phoneNumber, :studentType, :admissionYear, :previousSchool, :studentId, :yearOfStudy, :duesPaid)");

            $stmt->execute([
                ':fullName' => $registrationData['fullName'],
                ':program' => $registrationData['program'],
                ':email' => $registrationData['email'],
                ':phoneNumber' => $registrationData['phoneNumber'],
                ':studentType' => $registrationData['studentType'],
                ':admissionYear' => $registrationData['admissionYear'] ?? null, // Use null if not applicable
                ':previousSchool' => $registrationData['previousSchool'] ?? null,
                ':studentId' => $registrationData['studentId'] ?? null,
                ':yearOfStudy' => $registrationData['yearOfStudy'] ?? null,
                ':duesPaid' => $registrationData['duesPaid']
            ]);

            $studentId_db = $pdo->lastInsertId(); // Get the ID of the newly inserted student

            // Example: Insert selected courses into a 'student_courses' junction table
            $courseStmt = $pdo->prepare("INSERT INTO student_courses (student_id, course_name) VALUES (:studentId, :courseName)");
            foreach ($registrationData['courses'] as $course) {
                $courseStmt->execute([
                    ':studentId' => $studentId_db,
                    ':courseName' => $course
                ]);
            }

            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Registration successful! Your data has been saved.';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['message'] = 'Database error: ' . $e->getMessage();
            // Log the error for debugging: error_log($e->getMessage());
        }
        */

        // For this example, we'll just return success as if it were saved
        $response['success'] = true;
        $response['message'] = 'Registration successful! Your data has been processed.';


    } else {
        $response['message'] = implode('<br>', $errors); // Join all errors for display
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

?>