<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

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

  body {
    width: 816px;
    height: 1056px;
  }

  td { 
    padding-left: 10px;
    padding-right: 10px;
  }


  </style>

</head>

<body>

  <span class="d-flex justify-content-center" style="font-family:Arial,sans-serif; font-size:x-large"><strong>ADDESSA CORPORATION</strong></span>
  <span class="d-flex justify-content-center" style="font-family:Arial,sans-serif; font-size:small">Information System</span>
  <span class="d-flex justify-content-center" style="font-family:Arial,sans-serif; font-size:x-large"><strong>IS SERVICE ACCEPTANCE</strong></span>
  <br>
  <table class="mb-4" border="1" cellpadding="0" cellspacing="0" style="width:100%;">
    <tbody>
      <tr>
        <td>
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Accepted By</strong></span>
        </td>
        <td colspan="3">
          <span style="font-family:Arial,sans-serif; font-size:small">{{ $acceptance_overview->validator }}</span>
        </td>
      </tr>
      <tr>
        <td>
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Department</strong></span>
        </td>
        <td colspan="3">
          <span style="font-family:Arial,sans-serif; font-size:small">{{ $acceptance_overview->department }}</span>
        </td>
      </tr>
      <tr>
        <td>
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Reference #</strong></span>
        </td>
        <td>
          <span style="font-family:Arial,sans-serif; font-size:small">{{ $acceptance_overview->ref_no }}</span>
        </td>
        <td>
          <div class="d-flex justify-content-end mr-4">
            <span style="font-family:Arial,sans-serif; font-size:small"><strong>Date Accepted: &nbsp;</strong></span>
            <span style="font-family:Arial,sans-serif; font-size:small">{{ $acceptance_overview->accepted_date }}</span>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Project Name</strong></span>
        </td>
        <td colspan="3">
          <span style="font-family:Arial,sans-serif; font-size:small">{{ $acceptance_overview->report_title }}</span>
        </td>
      </tr>
    </tbody>
  </table>

  <span style="font-family:Arial,sans-serif; font-size:small"><strong>SAP Software / Database Administrator Documentation Overview (MIS)</strong></span>
  <table border="1" cellpadding="0" cellspacing="0" style="width:100%; padding: 4px;">
    <tbody>
      <tr height="300px">
        <td colspan="2" style="vertical-align:TOP">
          <span class="ml-4" style="font-family:Arial,sans-serif; font-size:small">
            {{ $acceptance_overview->overview }}
          </span>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="vertical-align:TOP">
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Intended Users: {{ $acceptance_overview->validator }}</strong></span>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="vertical-align:TOP">
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>For Delete (if any): </strong></span>
        </td>
      </tr>
      <tr height="50px">
        <td colspan="2" style="vertical-align:TOP">
          <span class="ml-4" style="font-family:Arial,sans-serif; font-size:small">
            {{ $acceptance_overview->for_delete }}
          </span>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="vertical-align:TOP">
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Location Inside SAP B1: </strong></span>
        </td>
      </tr>
      <tr height="50px">
        <td colspan="2" style="vertical-align:TOP">
          <span class="ml-4" style="font-family:Arial,sans-serif; font-size:small">
            {{ $acceptance_overview->location1 }}
          </span>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="vertical-align:TOP">
          <span style="font-family:Arial,sans-serif; font-size:small"><strong>Location Outside SAP B1: </strong></span>
        </td>
      </tr>
      <tr height="50px">
        <td colspan="2" style="vertical-align:TOP">
          <span class="ml-4" style="font-family:Arial,sans-serif; font-size:small">
            {{ $acceptance_overview->location2 }}
          </span>
        </td>
      </tr>
      <tr>
        <td>
          <div style="margin-left: 500px">
            <span style="font-family:Arial,sans-serif; font-size:small;"><strong>Name &amp; Signature: </strong>{{ $acceptance_overview->programmer }}</span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <span style="font-family:Arial,sans-serif; font-size:small"><strong>Validator&#39;s Acceptance</strong></span></span>

  <table border="1" cellpadding="0" cellspacing="0" style="width:100%">
    <tbody>
      <tr height="130px">
        <td colspan="2" style="vertical-align:TOP">
          <span class="ml-4" style="font-family:Arial,sans-serif; font-size:small">
            {{ $acceptance_overview->validator_note }}
          </span>
        </td>
      </tr>
      <tr>
        <td>
          <div style="margin-left: 500px">
            <span style="font-family:Arial,sans-serif; font-size:small;"><strong>Name &amp; Signature: </strong>{{ $acceptance_overview->validator }}</span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <span style="font-family:Arial,sans-serif; font-size:small"><strong>Deparment Manager&#39;s Acceptance</strong></span></span>

  <table border="1" cellpadding="0" cellspacing="0" style="width:100%">
    <tbody>
      <tr height="100px">
        <td colspan="2" style="vertical-align:TOP">
          <span class="ml-4" style="font-family:Arial,sans-serif; font-size:small">
            {{ $acceptance_overview->manager_note }}
          </span>
        </td>
      </tr>
      <tr>
        <td>
          <div style="margin-left: 500px">
            <span style="font-family:Arial,sans-serif; font-size:small;"><strong>Name &amp; Signature: </strong>{{ $acceptance_overview->manager }}</span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <span style="font-family:Arial,sans-serif"><span style="font-size:x-small">Private &amp; Confidential &ndash; Reproduction and use without prior written approval from management is strictly prohibited. REV. 1.8 22Nov2016</span></span>


  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

</body>

</html>