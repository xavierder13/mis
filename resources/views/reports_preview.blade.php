<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
  ADDESSA CORPORATION <br/>
  ASSIGNMENT WORKFLOW <br/>
  PROGRAMMER NAME: <br/>
  AS OF: <br/>

  <table>
    
    <thead>
      <td>Approved/ Filing Date</td>
      <td>Ref #</td>
      <td>Report Name</td>
      <td>Ideal</td>
      <td>Department</td>
      <td>Manager</td>
      <!-- Received Template Design -->
      <td>Date</td>
      <td>Running Qty</td>
      <td>%</td>
      <!-- Programming of Crystal Report -->
      <td>Date</td>
      <td>Running Qty</td>
      <td>%</td>
      <!-- Validation -->
      <td>Date</td>
      <td>Running Qty</td>
      <td>%</td>
      <td>Validator</td>
      <td>Report Type</td>
      <td>Validation Hour</td>
      <td>Programming Hours</td>
      <td>Validation Hours(This Month)</td>
      <td>Programming Hour(This Month)</td>
    </thead>
    <tbody>
      <tr><td colspan='21'></td></tr>
      <tr><td colspan='21'> For Validation</td></tr>
    @foreach($projects as $i => $project)
      @if($project->status == 'For Validation')
      <tr>
        <td>{{ $project->date_approved }}</td>
        <td>{{ $project->ref_no }}</td>
        <td>{{ $project->report_title }}</td>
        <td>{{ $project->ideal }}</td>
        <td>{{ $project->department }}</td>
        <td>{{ $project->manager }}</td>
        <td>{{ $project->date_received }}</td>
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
      <tr><td colspan='21'></td></tr>
      <tr><td colspan='21'> ONGOING</td></tr>
    @foreach($projects as $project)
      @if($project->status == 'Ongoing')
      <tr>
        <td>{{ $project->date_approved }}</td>
      </tr>
      @endif
    @endforeach 
      <tr><td colspan='21'></td></tr>
      <tr><td colspan='21'> PENDING</td></tr>
    @foreach($projects as $project)
      @if($project->status == 'Pending')
      <tr>
        <td>{{ $project->date_approved }}</td>
      </tr>
      @endif
    @endforeach 
      <tr><td colspan='21'></td></tr>
      <tr><td colspan='21'> ACCEPTED</td></tr>
    @foreach($projects as $project)
      @if($project->status == 'Accepted')
      <tr>
        <td>{{ $project->accepted_date }}</td>
      </tr>
      @endif
    @endforeach 
    </tbody>
  </table>
</body>
</html>