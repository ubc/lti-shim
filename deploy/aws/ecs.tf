resource "aws_ecs_cluster" "main" {
  name = "shim-cluster"
}

data "template_file" "shim_app" {
  template = file("./templates/ecs/shim_app.json.tpl")

  vars = {
    app_image      = var.app_image
    app_port       = var.app_port
    fargate_cpu    = var.fargate_cpu
    fargate_memory = var.fargate_memory
    aws_region     = var.aws_region
	db_address     = aws_db_instance.default.address
	db_port        = aws_db_instance.default.port
	db_username    = var.db_username
	db_password    = var.db_password
	admin_name     = var.admin_name
	admin_email    = var.admin_email
	admin_password = var.admin_password
  }
}

resource "aws_ecs_task_definition" "app" {
  family                   = "shim-app-task"
  execution_role_arn       = aws_iam_role.ecs_task_execution_role.arn
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = var.fargate_cpu
  memory                   = var.fargate_memory
  container_definitions    = data.template_file.shim_app.rendered
}

resource "aws_ecs_service" "main" {
  name            = "shim-service"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.app.arn
  desired_count   = var.app_count
  launch_type     = "FARGATE"

  network_configuration {
    security_groups  = [aws_security_group.ecs_tasks.id]
    subnets          = aws_subnet.private.*.id
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_alb_target_group.app.id
    container_name   = "shim-app"
    container_port   = var.app_port
  }

  depends_on = [aws_alb_listener.front_end, aws_iam_role_policy_attachment.ecs_task_execution_role]
}
