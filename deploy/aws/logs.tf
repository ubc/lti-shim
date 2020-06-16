# Set up CloudWatch group and log stream and retain logs for 30 days
resource "aws_cloudwatch_log_group" "shim_log_group" {
  name              = "/ecs/shim-app"
  retention_in_days = 30

  tags = {
    Name = "shim-log-group"
  }
}

resource "aws_cloudwatch_log_stream" "shim_log_stream" {
  name           = "shim-log-stream"
  log_group_name = aws_cloudwatch_log_group.shim_log_group.name
}
