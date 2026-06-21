<?php
require_once 'config/db.php';

if (!isset($_GET['id']))
    die("Invalid Request");
$id = $_GET['id']; // Appointment ID

// Fetch Data
$sql = "SELECT a.*, p.full_name, p.age, p.gender, p.mr_number, p.address as p_address, 
        u.full_name as doctor_name, pr.diagnosis, pr.advice 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN users u ON a.doctor_id = u.id 
        LEFT JOIN prescriptions pr ON a.id = pr.appointment_id 
        WHERE a.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data)
    die("Appointment not found");

// Fetch Medicines
$med_stmt = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = (SELECT id FROM prescriptions WHERE appointment_id = ?)");
$med_stmt->execute([$id]);
$meds = $med_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prescription - <?php echo $data['full_name']; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background: white;
            font-family: 'Times New Roman', serif;
        }

        .header {
            border-bottom: 3px solid #0066CC;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .footer {
            border-top: 1px solid #ccc;
            margin-top: 50px;
            padding-top: 10px;
            font-size: 12px;
        }

        .rx-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .rx-symbol {
            font-size: 40px;
            font-weight: bold;
            font-style: italic;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body class="p-4">

    <div class="container border p-0">
        <!-- Action Buttons -->
        <div class="text-end p-3 no-print bg-light border-bottom">
            <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Print Prescription</button>
            <a href="opd.php" class="btn btn-secondary btn-sm">⬅ Back</a>
        </div>

        <!-- Header -->
        <div class="p-4 border-bottom">
            <div class="row align-items-center">
                <div class="col-8">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo APP_LOGO; ?>" alt="Logo" style="height: 90px; width: auto;" class="me-3">
                        <div>
                            <h2 class="fw-bold text-primary m-0" style="font-family: 'Times New Roman', serif;">
                                <?php echo APP_NAME; ?></h2>
                            <small class="text-dark d-block fw-bold"><?php echo APP_ADDRESS; ?></small>
                            <small class="text-dark">📞 <?php echo APP_PHONE; ?> | ✉ <?php echo APP_EMAIL; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-4 text-end">
                    <div class="border p-2 bg-light rounded">
                        <h5 class="fw-bold text-primary mb-1">Dr. <?php echo $data['doctor_name']; ?></h5>
                        <small>MBBS, MD (Medicine)</small><br>
                        <small>Consultant Physician</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="p-3 border-bottom bg-light">
            <div class="row">
                <div class="col-4">
                    <strong>Patient Name:</strong> <?php echo $data['full_name']; ?><br>
                    <strong>Age/Gender:</strong> <?php echo $data['age']; ?> Y / <?php echo $data['gender']; ?>
                </div>
                <div class="col-4">
                    <strong>Address:</strong> <?php echo $data['p_address']; ?><br>
                    <strong>Contact:</strong> <?php echo $data['phone'] ?: 'N/A'; ?>
                </div>
                <div class="col-4 text-end">
                    <strong>Date:</strong> <?php echo date('d-M-Y', strtotime($data['visit_date'])); ?><br>
                    <strong>OPD ID:</strong> <?php echo $data['token_number']; ?> | <strong>MR No:</strong>
                    <?php echo $data['mr_number']; ?>
                </div>
            </div>
        </div>

        <!-- Vitals Strip -->
        <div class="p-2 border-bottom text-center bg-white">
            <span class="mx-3"><strong>BP:</strong> <?php echo $data['bp'] ?: '--'; ?> mmHg</span> |
            <span class="mx-3"><strong>Pulse:</strong> <?php echo $data['pulse'] ?: '--'; ?> bpm</span> |
            <span class="mx-3"><strong>Temp:</strong> <?php echo $data['temperature'] ?: '--'; ?> °F</span> |
            <span class="mx-3"><strong>Wt:</strong> <?php echo $data['weight'] ?: '--'; ?> kg</span>
        </div>

        <!-- Main Content -->
        <div class="row p-4" style="min-height: 500px;">
            <!-- Left Column: Symptoms/Diagnosis -->
            <div class="col-4 border-end">
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-decoration-underline">Symptoms / Chief Complaints</h6>
                    <p><?php echo nl2br($data['symptoms'] ?: 'As per patient history'); ?></p>
                </div>
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-decoration-underline">Diagnosis</h6>
                    <p><?php echo nl2br($data['diagnosis']); ?></p>
                </div>
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-decoration-underline">Advice / Investigations</h6>
                    <p><?php echo nl2br($data['advice']); ?></p>
                </div>
            </div>

            <!-- Right Column: Rx -->
            <div class="col-8 ps-4">
                <h3 class="rx-symbol text-primary">Rx</h3>

                <table class="table table-borderless table-striped mt-3">
                    <thead class="border-bottom">
                        <tr>
                            <th width="40%">Medicine Name</th>
                            <th>Dosage</th>
                            <th>Duration</th>
                            <th>Instruction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meds as $m): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $m['medicine_name']; ?></td>
                                <td><?php echo $m['dosage']; ?></td>
                                <td><?php echo $m['duration']; ?></td>
                                <td><small class="text-muted"><?php echo $m['instruction']; ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-top mt-auto">
            <div class="row align-items-end">
                <div class="col-6">
                    <small class="text-muted">
                        * Not valid for medico-legal purpose<br>
                        * In case of emergency, contact immediately<br>
                        * Please bring this prescription on next visit
                    </small>
                </div>
                <div class="col-6 text-end">
                    <img src="" alt="Signature" style="height: 50px; display: none;" class="mb-2">
                    <p class="fw-bold border-top d-inline-block pt-2 px-5">Dr. <?php echo $data['doctor_name']; ?></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>