<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <style>
  .table-condensed{
    font-size: 10px;
  }
  .bold {
    font-weight: bold;
  }
  </style>
</head>
<body>
  <div class="row mb-5">
    <div class="col-md-12 bold">
      ADDESSA CORPORATION <br/>
      ASSIGNMENT WORKFLOW <br/>
      PROGRAMMER NAME: {{ strtoupper($programmer) }}<br/>
      AS OF: {{ date('m/d/Y', strtotime($filter_date)) }}<br/>
    </div>    
  </div>
  
  <div class="row">
    <div class="col-md-12">
      <table class="table table-bordered w-auto small">
        <thead class="table-dark bold">
          <td>Approved/ Filing Date</td>
          <td>Ref #</td>
          <td>Report Name</td>
          <td>Ideal Prog Hrs.</td>
          <td>Ideal Valid Hrs.</td>
          <td>Department</td>
          <td>Manager</td>
          <!-- Received Template Design -->
          <td>Template <br/> Date Received</td>
          <td>Run. Qty</td>
          <td>Template %</td>
          <!-- Programming of Crystal Report -->
          <td>Prog. <br/> Date <br/> Started</td>
          <td>Run. Qty</td>
          <td>Prog. %</td>
          <!-- Validation -->
          <td>Valdtn. <br/> Date <br/> Started</td>
          <td>Run. Qty</td>
          <td>Valdtn. %</td>
          <td>Validator</td>
          <td>Report Type</td>
          <td>Valdtn. Hrs</td>
          <td>Prog. Hrs</td>
          <td>Valdtn. Hrs <br/> (This Month)</td>
          <td>Prog. Hrs <br/> (This Month)</td>
        </thead>
        <tbody>
          <tr><td colspan='22'></td></tr>
          <tr class="table-primary bold"><td colspan='22'> For Validation</td></tr>
        @foreach($projects as $i => $project)
          @if($project->status == 'For Validation')
          <tr>
            <td>{{ $project->date_approve }}</td>
            <td>{{ $project->ref_no }}</td>
            <td class="table-condensed">{{ $project->report_title }}</td>
            <td>{{ $project->ideal_prog_hrs }}</td>
            <td>{{ $project->ideal_valid_hrs }}</td>
            <td>{{ $project->department }}</td>
            <td>{{ $project->manager }}</td>
            <td>{{ $project->date_receive }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->template_percent }}</td>
            <td>{{ $project->program_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->program_percent }}</td>
            <td>{{ $project->validation_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->validation_percent }}</td>
            <td>{{ $project->validator }}</td>
            <td>{{ $project->type }}</td>
            
            @foreach($project_execution_hrs as $i => $exec_hrs)
              @if($project->project_id == $exec_hrs['project_id'])
              <!-- Validation Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['validate_hrs'] }}</td>
              <!-- Program Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['program_hrs'] }}</td>
              <!-- Validation Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['validate_hrs'] }}</td>
              <!-- Program Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['program_hrs'] }}</td>
              @endif
            @endforeach
          </tr>
          @endif
        @endforeach
          <tr><td colspan='22'></td></tr>
          <tr class="table-secondary bold"><td colspan='22'> ONGOING</td></tr>
        @foreach($projects as $i => $project)
          @if($project->status == 'Ongoing')
          <tr>
            <td>{{ $project->date_approve }}</td>
            <td>{{ $project->ref_no }}</td>
            <td class="table-condensed">{{ $project->report_title }}</td>
            <td>{{ $project->ideal_prog_hrs }}</td>
            <td>{{ $project->ideal_valid_hrs }}</td>
            <td>{{ $project->department }}</td>
            <td>{{ $project->manager }}</td>
            <td>{{ $project->date_receive }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->template_percent }}</td>
            <td>{{ $project->program_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->program_percent }}</td>
            <td>{{ $project->validation_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->validation_percent }}</td>
            <td>{{ $project->validator }}</td>
            <td>{{ $project->type }}</td>
            
            @foreach($project_execution_hrs as $i => $exec_hrs)
              @if($project->project_id == $exec_hrs['project_id'])
              <!-- Validation Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['validate_hrs'] }}</td>
              <!-- Program Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['program_hrs'] }}</td>
              <!-- Validation Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['validate_hrs'] }}</td>
              <!-- Program Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['program_hrs'] }}</td>
              @endif
            @endforeach
          </tr>
          @endif
        @endforeach 
          <tr><td colspan='22'></td></tr>
          <tr class="table-warning bold"><td colspan='22'> PENDING</td></tr>
        @foreach($projects as $i => $project)
          @if($project->status == 'Pending')
          <tr>
            <td>{{ $project->date_approve }}</td>
            <td>{{ $project->ref_no }}</td>
            <td class="table-condensed">{{ $project->report_title }}</td>
            <td>{{ $project->ideal_prog_hrs }}</td>
            <td>{{ $project->ideal_valid_hrs }}</td>
            <td>{{ $project->department }}</td>
            <td>{{ $project->manager }}</td>
            <td>{{ $project->date_receive }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->template_percent }}</td>
            <td>{{ $project->program_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->program_percent }}</td>
            <td>{{ $project->validation_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->validation_percent }}</td>
            <td>{{ $project->validator }}</td>
            <td>{{ $project->type }}</td>
            
            @foreach($project_execution_hrs as $exec_hrs)
              @if($project->project_id == $exec_hrs['project_id'])
              <!-- Validation Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['validate_hrs'] }}</td>
              <!-- Program Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['program_hrs'] }}</td>
              <!-- Validation Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['validate_hrs'] }}</td>
              <!-- Program Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['program_hrs'] }}</td>
              @endif
            @endforeach
          </tr>
          @endif
        @endforeach 
          <tr><td colspan='22'></td></tr>
          <tr class="table-success bold"><td colspan='22'> ACCEPTED</td></tr>
        @foreach($projects as $i => $project)
          @if($project->status == 'Accepted')
          <tr>
            <td>{{ $project->accepted_date }}</td>
            <td>{{ $project->ref_no }}</td>
            <td class="table-condensed">{{ $project->report_title }}</td>
            <td>{{ $project->ideal_prog_hrs }}</td>
            <td>{{ $project->ideal_valid_hrs }}</td>
            <td>{{ $project->department }}</td>
            <td>{{ $project->manager }}</td>
            <td>{{ $project->date_receive }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->template_percent }}</td>
            <td>{{ $project->program_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->program_percent }}</td>
            <td>{{ $project->validation_date }}</td>
            <td>{{ $i }}</td>
            <td>{{ $project->validation_percent }}</td>
            <td>{{ $project->validator }}</td>
            <td>{{ $project->type }}</td>
            
            @foreach($project_execution_hrs as $exec_hrs)
              @if($project->project_id == $exec_hrs['project_id'])
              <!-- Validation Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['validate_hrs'] }}</td>
              <!-- Program Hours Overall -->
              <td>{{ $exec_hrs['execution_hrs']['program_hrs'] }}</td>
              <!-- Validation Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['validate_hrs'] }}</td>
              <!-- Program Hours This Month -->
              <td>{{ $exec_hrs['execution_hrs_tm']['program_hrs'] }}</td>
              @endif
            @endforeach
          </tr>
          @endif
        @endforeach 
        </tbody>
      </table>
    </div>
  </div>
  

  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

</body>
</html>